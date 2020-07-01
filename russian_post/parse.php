<?php

require_once '../m/cache.php';

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

foreach ($a as $a)
	cache::set('pochta/'.$a['postalCode'], $a);

send($ref);


function send($ref)
{
	$a = cache::get('pochta/'.$ref, null, 'week');
	if (is_null($a)) return false;
	header('Content-type: application/json');
	echo json_encode($a, JSON_UNESCAPED_UNICODE);
	return true;
}
