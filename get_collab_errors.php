<?php
$log = file_get_contents('storage/logs/laravel.log');
preg_match_all('/\[\d{4}-\d{2}-\d{2}.*?\].*?CollaborationController.*?(?=\[\d{4}-\d{2}-\d{2}|$)/s', $log, $matches);
file_put_contents('collab_errors.txt', implode("\n", array_slice($matches[0], -5)));
