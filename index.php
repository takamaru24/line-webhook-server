<?php
// Heroku + LINE Messaging API + Google Drive 連携用Webhookサーバー

require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;

// LINEのアクセストークンをHerokuの環境変数から取得
$channelAccessToken = getenv('CHANNEL_ACCESS_TOKEN');

// Googleの認証情報（JSON形式文字列）を環境変数から取得してオブジェクトに変換
$googleCredentials = json_decode(getenv('GOOGLE_CREDENTIALS_JSON'), true);

// LINEからのPOSTデータを取得
$body = file_get_contents('php://input');
file_put_contents("log.txt", $body . PHP_EOL, FILE_APPEND); // ログ保存（任意）

$data = json_decode($body, true);
if (!isset($data['events'][0]['message']['id'])) {
    http_response_code(200);
    exit('No message ID');
}

$messageId = $data['events'][0]['message']['id'];
$messageType = $data['events'][0]['message']['type'];

// LINEからファイルを取得
$contentUrl = "https://api-data.line.me/v2/bot/message/{$messageId}/content";
$headers = [
    "Authorization: Bearer $channelAccessToken"
];

$ch = curl_init($contentUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    http_response_code(500);
    exit('LINE content fetch failed');
}

// Google Drive へアップロードする設定
$client = new Client();
$client->setAuthConfig($googleCredentials);
$client->addScope(Drive::DRIVE_FILE);
$drive = new Drive($client);

$extension = ($messageType === 'image') ? '.jpg' : '.pdf';
$filename = 'LINE_' . date('Ymd_His') . $extension;

$fileMetadata = new Drive\DriveFile([
    'name' => $filename,
    // 'parents' => ['任意のフォルダID'] ← 必要あれば設定
]);

$file = $drive->files->create($fileMetadata, [
    'data' => $response,
    'mimeType' => ($messageType === 'image') ? 'image/jpeg' : 'application/pdf',
    'uploadType' => 'multipart'
]);

echo "OK";
