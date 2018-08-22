#!/bin/env php
<?php

$data = ['nodes'=>[], 'ways'=>[], 'relations'=>[]];

parse('56.2480951,43.9418364,56.2533404,43.9514494'); // Мызинский мост
parse('56.2983246,43.9575005,56.2993485,43.9664698'); // Молитовский мост
parse('56.3167747,43.9576292,56.3221772,43.9702034'); // Метромост
parse('56.3255800,43.9676714,56.3304578,43.9775419'); // Канавинский мост

function parse($coords)
{
	global $data;
	$q = 'http://osm.cupivan.ru/overpass/?data='.
		urlencode('[out:json];(rel["type"="route"]('.$coords.'););(._;>;);out body;');
	$a = file_get_contents($q);
	$a = json_decode($a, true);

	foreach ($a['elements'] as $a)
	{
		$id = $a['id']; $type = $a['type'];
		unset($a['type']); unset($a['id']);
		if ($type == 'node')     $data['nodes'][$id] = $a;
		if ($type == 'way')      $data['ways'][$id]  = $a;
		if ($type == 'relation') $data['relations'][$id] = $a;
	}
}

$_ = $data; $routes=[];
foreach ($_['relations'] as $rid => $a)
{
	$st = '';
	$data = ['ref'=>$a['tags']['ref'],'stops'=>[]];
	$ref = $a['tags']['ref'];
	$routes[$rid] = ['ref'=>$ref, 'stops'=>[]];
	foreach ($a['members'] as $a)
	if ($a['role'] == 'stop')
	{
		$data['stops'][] = $a['ref'];
		$routes[$rid]['stops'][] = $a['ref'];
	}
}

$nodes = [];
$st = "var routes={};\n";
foreach ($routes as $k => $v)
{
	$st .= 'routes['.$k.'] = '.json_encode($v, JSON_UNESCAPED_UNICODE)."\n";
	foreach ($v['stops'] as $id)
	if (!empty($_['nodes'][$id]['lat']))
	{
		$a = $_['nodes'][$id];
		$a = ['c'=>[$a['lat'],$a['lon']], 'n'=>$a['tags']['name']];
		$nodes[$id] = $a;
	}
}
file_put_contents('routes.js', $st);

$st = "var nodes={};\n";
foreach ($nodes as $k => $v)
{
	$st .= 'nodes['.$k.'] = '.json_encode($v, JSON_UNESCAPED_UNICODE)."\n";
}
file_put_contents('nodes.js', $st);
