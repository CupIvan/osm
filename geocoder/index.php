<?php

header('Content-type: application/json');

if (empty($_REQUEST['city']))   die('{error: "empty city"}');
if (empty($_REQUEST['street'])) die('{error: "empty street"}');

$access = array();
$access[] = 'https://osm.cupivan.ru';
$access[] = 'https://xn----jtbhabjjheh2cq.xn--p1ai';
$access[] = 'http://_.xn----jtbhabjjheh2cq.xn--p1ai';
$headers = getallheaders();
if (!empty($headers['Origin']))
if (in_array($headers['Origin'], $access))
	header('Access-Control-Allow-Origin: '.$headers['Origin']);

$bounds = get_city_bounds(@$_REQUEST['city']);

if (!$bounds) die('{error: "unknown city"}');

$addr = @$_REQUEST['street'];

$query = '["building"]';

// раскрываем сокращения
$addr = str_replace(
	   ['ул.',   'пр.',    'пр-д',   'пр-т',     'пер.',     'пер ',     'мкр'],
	$t=['улица', 'проезд', 'проезд', 'проспект', 'переулок', 'переулок', 'микрорайон'],
	$addr);

// фильтр по улице
$street = ''; $type = ''; $types = implode('|', array_unique($t));
if (preg_match("#($types)\s*([^,]+)#",  $addr, $m)) { $street = $m[2]; $type = $m[1]; }
if (preg_match("#([^,]+?)\s*($types)#", $addr, $m)) { $street = $m[1]; $type = $m[2]; }

$reverse = true; // true=улица Героев, false=Садовая улица
if (preg_match('/(ая|ый|ий)$/', $street)) $reverse = false;
$street = $reverse ? "$type $street" : "$street $type";

if ($street)
	$query .= '["addr:street"="'.$street.'"]';
else die('{error: "unknown street"}');

// фильтр по дому
$house = '';
if (preg_match('#д\.\s*([0-9А-Я]+)#', $addr, $m))
	$house .= $m[1];
if (preg_match('#корп\.\s*([0-9]+)#', $addr, $m))
	$house .= ' к'.$m[1];
if ($house)
	$query .= '["addr:housenumber"="'.$house.'"]';

$flat = '';
if (preg_match('#кв.\s*([0-9]+)#', $addr, $m))
	$flat = $m[1];

$query = "[out:json];
nwr
$bounds
$query;
(._;>;);
out body;";

$json = file_get_contents('https://osm.cupivan.ru/overpass/?data='.urlencode($query));
$a = json_decode($json, true);

if (empty($a['elements'])) die('{error: "empty result"}');


// перебираем все элементы и ищем центр полигона
$nodes = []; $ways = [];
foreach ($a['elements'] as $a)
{
	if ($a['type'] == 'node') $nodes[$a['id']] = $a;
	if ($a['type'] == 'way')  $ways[$a['id']] = array_map(function($nodeId) use($nodes) { return $nodes[$nodeId]; }, $a['nodes']);
	if (@$a['tags']['addr:street']      == $street)
	{
		$center = get_center($a, $flat);
		if (@$a['tags']['addr:housenumber'] == $house)
		{
			echo json_encode($center);
			exit;
		}
	}
}

// если попали сюда - значит номер дома не найден
$center['type'] = 'street';
echo json_encode($center);
exit;


/** центральная точка полигона или координаты входа */
function get_center($a, $flat)
{
	global $ways;

	$center = ['lat'=>0, 'lon'=>0, 'type'=>'building', 'id'=>$a['type'][0].$a['id']];

	if ($a['type'] == 'node') return array_intersect_key($a, $center);

	$w = [];
	// список веев для обработки
	if ($a['type'] == 'way') $w[] = $a['id'];
	if ($a['type'] == 'relation')
		foreach ($a['members'] as $_)
			if ($_['type'] == 'way' && $_['role'] == 'outer') $w[] = $_['ref'];

	$n = 0;
	foreach ($w as $wayId)
	foreach ($ways[$wayId] as $node)
	{
		// нашли подъезд для этой квартиры
		if (!empty($node['tags']['addr:flats']))
		{
			$x = explode('-', $node['tags']['addr:flats']);
			if ($flat >= $x[0] && $flat <= $x[1])
				return ['type'=>'entrance'] + array_intersect_key($node, $center);
		}
		$center['lat'] += $node['lat'];
		$center['lon'] += $node['lon'];
		$n++;
	}
	$center['lat'] /= $n;
	$center['lon'] /= $n;

	return $center;
}


/** bbox города для поиска адреса */
function get_city_bounds($city)
{
	$lat_min = $lot_min = $lat_max = $lon_max = NULL;
	if ($city == 'Нижний Новгород')
		return '(56.14478445312653,43.73313903808594,56.47235255844717,44.22271728515624)';
	return "(0,0,0,0)";
	return "($lat_min,$lon_min,$lat_max,$lon_max)";
}
