<?php

$query = @$_GET['query'];

$fname = './cache/'.md5($query).'.json';

$log = date('H:i:s d.m.Y')." - ".$_SERVER['REMOTE_ADDR'];

// очистка старых кешей каждые ~100 запросов
if (!mt_rand(0, 100)) { exec('find ./cache/ -mtime +7 -delete'); $log .= " CLEAR"; }

if (file_exists($fname) && filemtime($fname) > time() - 2*3600)
{
	$page = file_get_contents($fname);
	$log .= ' CACHE';
}

if (empty($page))
{
	@file_put_contents($fname, '{}');
	$server = 'http://overpass-api.de/api/';
	$page = @file_get_contents("$server/interpreter?data=".urlencode($query));
	if ($page) @file_put_contents($fname, $page); else unlink($fname);
	$log .= ' DOWNLOAD';
}

$log .= " - $fname - $query\n";

@file_put_contents('./cache/'.date('Y-m-d').'.log', $log, FILE_APPEND);

echo $page;
