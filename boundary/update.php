#!/bin/env php
<?php

define('CACHE_DIR', '/tmp/cache/osm');
@mkdir(CACHE_DIR,   0777, true);
@mkdir('./geojson', 0777, true);

require_once 'osm.php';

define('OSM_ID_RUSSIA', 60189); // id отношения Россия

// получаем все id регионов
$regions = [];
$russia = osm_get_relation(OSM_ID_RUSSIA);
foreach (osm_get_subareas($russia) as $id) // COMMENT здесь получили федеральные округа
{
	$regions = array_merge($regions, osm_get_subareas(osm_get_relation($id)));
}

$all = [];
foreach ($regions as $id)
{
	echo "Make #$id... ";
	$geo = osm_make_boundary($id, osm_get_relation($id, true));
	$fname = @$geo['properties']['ref'];
	if (!$fname) $fname = $geo['id'];
	file_put_contents($fname = './geojson/'.$fname.'.json', json_encode($geo, JSON_UNESCAPED_UNICODE));
	echo "$fname [OK]\n";
	$all[] = $fname;
	file_put_contents('./geojson/all.json', json_encode($all));
}
