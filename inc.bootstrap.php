<?php

require 'env.php';
require 'inc.functions.php';

$_start = microtime(1);

header('Content-type: text/html; charset=utf-8');

define('BOOKMARKS_CACHE_TTL', 300);
define('DT', 'd M H:i');
