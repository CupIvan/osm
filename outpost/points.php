<?php

if (@$_REQUEST['brand'] == 'Wildberries')
	$url = 'https://www.wildberries.ru/services/besplatnaya-dostavka';

if (empty($url)) die('error');

$cache = 'cache.'.$_REQUEST['brand'].'.json';
if (time() - @filemtime($cache) < 7*24*3600) { echo file_get_contents($cache); exit; }

$st = file_get_contents($url);
$n1 = strpos($st, '= [{');
$n2 = strpos($st, '}];');
$st = substr($st, $n1+2, $n2-$n1);

foreach(json_decode($st, true) as $a)
{
	$coords = explode(',', $a['coordinates']);
	$res[] = [
		'lat' => $coords[0],
		'lon' => $coords[1],
		'h' => $a['workTime'],
	];
}

echo $st = json_encode($res, JSON_UNESCAPED_UNICODE);
if ($st) file_put_contents($cache, $st);
