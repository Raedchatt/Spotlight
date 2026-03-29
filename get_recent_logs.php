<?php
$log = file_get_contents('storage/logs/laravel.log');
preg_match_all('/\[\d{4}-\d{2}-\d{2}.*?\].*?(?=\[\d{4}-\d{2}-\d{2}|$)/s', $log, $matches);
$lastLogs = array_slice($matches[0], -20);
file_put_contents('recent_logs.txt', implode("\n", $lastLogs));
