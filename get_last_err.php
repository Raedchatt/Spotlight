<?php
$log = file_get_contents('storage/logs/laravel.log');
preg_match_all('/\[\d{4}-\d{2}-\d{2}.*?\].*?(?=\[\d{4}-\d{2}-\d{2}|$)/s', $log, $matches);
$lastLog = end($matches[0]);
$lines = explode("\n", $lastLog);
file_put_contents('last_error.txt', implode("\n", array_slice($lines, 0, 15)));
