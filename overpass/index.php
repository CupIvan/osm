<?php

if (strpos(@$_SERVER['HTTP_ORIGIN'], 'cupivan.ru'))
	header('Access-Control-Allow-Origin: *');

define('DIR', '/tmp/cache_osm/');
if (!file_exists(DIR)) mkdir(DIR, 0777, true);

$data = @$_GET['data'];
$data = preg_replace('/\s+/', ' ', $data);

$fname = DIR.md5($data).'.json';

$log = date('H:i:s d.m.Y')."\t".$_SERVER['REMOTE_ADDR']."\t";

// очистка старых кешей каждые ~100 запросов
if (!mt_rand(0, 100)) { exec('find '.DIR.' -mtime +7 -delete'); $log .= " CLEAR"; }

if (file_exists($fname) && filemtime($fname) > time() - 2*3600)
{
	$page = file_get_contents($fname);
	$log .= 'CACHE';
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
	if ($page) @file_put_contents($fname, $page); else { @unlink($fname); $page = '{"state": "error", "url": "'.$url.'"}'; }
	$log .= 'DOWNLOAD';
}

$log .= "\t".round(strlen($page)/1024)."Kb\t$fname\t$data\n";

@file_put_contents(DIR.date('Y-m-d').'.log', $log, FILE_APPEND);

echo $page;
