<?php

if (strpos(@$_SERVER['HTTP_ORIGIN'], 'cupivan.ru'))
	header('Access-Control-Allow-Origin: *');

$data = @$_GET['data'];

$fname = './cache/'.md5($data).'.json';

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
	$servers = [
		'https://lz4.overpass-api.de/api/interpreter',
		'https://z.overpass-api.de/api/interpreter',
		'http://overpass.openstreetmap.fr/api/interpreter',
		'https://overpass.kumi.systems/api/interpreter',
	];
	if (strpos($_SERVER['HTTP_HOST'], '_.osm') !== false)
		$servers = ['https://osm.cupivan.ru/overpass'];
	else
		@file_put_contents($fname, '{}');
	$server = $servers[mt_rand(0, count($servers)-1)];
	$url  = "$server/?data=".urlencode($data);
	$page = @file_get_contents($url);
	if ($page) @file_put_contents($fname, $page); else { unlink($fname); $page = '{"state": "error", "url": "'.$url.'"}'; }
	$log .= ' DOWNLOAD';
}

$log .= " - $fname - $data\n";

@file_put_contents('./cache/_'.date('Y-m-d').'.log', $log, FILE_APPEND);

echo $page;
