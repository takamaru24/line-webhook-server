<?php
// LINE Bot Webhook（Heroku対応）

$accessToken = getenv('CHANNEL_ACCESS_TOKEN'); // ← HerokuのConfig Varsから取得

$input = file_get_contents('php://input');
file_put_contents('log.txt', $input . PHP_EOL, FILE_APPEND); // 受信ログを残す

http_response_code(200); // LINEに「受け取ったよ」と返す
