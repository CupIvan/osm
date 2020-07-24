<?php

require_once '../m/cache.class.php';

if (!preg_match('/^\d{6}$/', @$_REQUEST['ref'])) die('[]');

$ref = $_REQUEST['ref'];

if (send($ref)) exit;

$json = [
	'postalCode' => $ref,
	'limit'      => 100,
	'radius'     => 1000,
];
$json = json_encode($json);
$context  = stream_context_create(['http' => [
	'method'  => 'POST',
	'header'  => "Content-type: application/json\r\nContent-Length: ".strlen($json)."\r\n",
	'content' => $json,
]]);
$url = 'https://www.pochta.ru/suggestions/v2/postoffice.find-nearest-by-postalcode-raw-filters';
$st = file_get_contents($url, false, $context);
$a = json_decode($st, true);

require_once '../m/mysql.class.php';
mysql::$base = 'opendata';
mysql::$user = 'opendata';
mysql::$pass = 'opendata';

foreach ($a as $a)
{
	mysql::insert_duplicate('russian_post', [
		'ref'  => $a['postalCode'],
		'lat'  => $a['latitude'],
		'lon'  => $a['longitude'],
		'json' => json_encode($a, JSON_UNESCAPED_UNICODE),
		'timestamp'     => date('Y-m-d H:i:s'),
		'city'          => @$a['address']['settlementOrCity'],
		'address'       => @$a['addressSource'],
		'opening_hours' => _oh($a),
	]);
	cache::set('pochta/'.$a['postalCode'], $a);
}

send($ref);


function send($ref)
{
	$a = cache::get('pochta/'.$ref, null, 'week');
	if (is_null($a)) return false;
	header('Content-type: application/json');
	echo json_encode($a, JSON_UNESCAPED_UNICODE);
	return true;
}

/** формирование opening_hours */
function _oh($a)
{
	if (!empty($a['isClosed']) || !empty($a['isTemporaryClosed'])) return 'closed';
	if (empty($a['workingHours'])) return '';

	$_d = ['', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
	$tmp = [];

	// объединяем дни с одинаковым режимом работы
	foreach ($a['workingHours'] as $h)
	if (!empty($h['beginWorkTime']))
	{
		$time = $h['beginWorkTime'].'-'.$h['endWorkTime'];
		if (!empty($h['lunches']))
			$time = str_replace('-', '-'.@$h['lunches'][0]['beginLunchTime'].','.@$h['lunches'][0]['endLunchTime'].'-', $time);
		$time = preg_replace('/(:\d{2}):00/', '$1', $time);
		@$tmp[$time][] = ($_d[$h['weekDayId']]);
	}

	// формируем строку opening_hours
	$hours = '';
	foreach ($tmp as $time => $a)
		$hours .= ($hours?'; ':'') . implode(',', $a).' '.$time;

	// склеиваем смежные дни
	for ($i=2; $i<count($_d); $i++)
	{
		$pre = $_d[$i-1]; $cur = $_d[$i];
		$hours = str_replace("$pre,$cur",  "$pre-$cur", $hours);
		$hours = str_replace("-$pre-$cur", "-$cur",     $hours);
	}

	return $hours;
}
