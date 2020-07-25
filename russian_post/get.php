<?php

if (empty($_REQUEST['rect'])) die('[]');

require_once '../m/mysql.class.php';
mysql::$base = 'opendata';
mysql::$user = 'opendata';
mysql::$pass = 'opendata';

$a = explode(',', $_REQUEST['rect']);
$sql = mysql::prepare('SELECT lat, lon, city, address, ref, opening_hours, DATE_FORMAT(timestamp, "%d.%m.%Y") as date FROM russian_post
WHERE lat > ?f AND lat < ?f AND lon > ?f AND lon < ?f', $a[1], $a[3], $a[0], $a[2]);

$res = mysql::getList($sql);

header('Content-type: application/json');
echo json_encode($res, JSON_UNESCAPED_UNICODE);
