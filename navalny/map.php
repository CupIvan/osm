<?php

$url = 'https://shtab.navalny.com/hq/map.json';
$fname = '/tmp/navalny.json';
if (time() - @filemtime($fname) < 24*3600)
	$url = $fname;
$st = file_get_contents($url);
echo $st;

if ($url[0] != '/' && $st) file_put_contents($fname, $st);
