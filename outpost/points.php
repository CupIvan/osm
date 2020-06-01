<?php

switch (@$_REQUEST[$x='brand'])
{
	case 'Wildberries':
		echo get_points($_REQUEST[$x], function(){
			$st = get_page('https://www.wildberries.ru/services/besplatnaya-dostavka');
			$n1 = strpos($st, '= [{');
			$n2 = strpos($st, '}];');
			$st = substr($st, $n1+2, $n2-$n1);

			foreach (json_decode($st, true) as $a)
			{
				$coords = explode(',', $a['coordinates']);
				$res[] = [
					'lat' => $coords[0],
					'lon' => $coords[1],
					'h' => $a['workTime'],
				];
			}

			return $res;
		});
		break;
	case 'Labirint':
		echo get_points($_REQUEST[$x], function(){
			$st = get_page('https://labirint.ru/maps/');
			$n1 = strpos($st, 'var json = \'{"center');
			$n2 = strpos($st, 'var yaMapsCity');
			$st = substr($st, $n1+12, $n2-$n1-20);

			foreach (json_decode($st, true)['points'] as $a)
			if ($a['icontype'] == 'dl')
			{
				$coords = explode(',', $a['coordinates']);
				$res[] = [
					'lat' => $a['x'],
					'lon' => $a['y'],
				];
			}

			return $res;
		});
		break;
	case 'Onlinetrade':
		echo get_points($_REQUEST[$x], function(){
			$st = get_page('https://www.onlinetrade.ru/shops/');
			$n1 = strpos($st, 'var firstUse');
			$n2 = strpos($st, 'YMapsID_shoplist');
			$st = substr($st, $n1, $n2-$n1);
			if (preg_match_all('#Lon.+?(\d[\d.]+).+?Lat.+?(\d[\d.]+)#', $st, $m, PREG_SET_ORDER))
			foreach ($m as $a)
			{
				$res[] = [
					'lat' => $a[2],
					'lon' => $a[1],
				];
			}

			return $res;
		});
		break;
}

function get_points($brand, $parser)
{
	$cache = '/tmp/cache_osm/'.$_REQUEST['brand'].'.json';
	$dir = dirname($cache);
	if (!file_exists($dir)) mkdir($dir, 0777, true);
	if (time() - @filemtime($cache) < 7*24*3600) return file_get_contents($cache);

	$res = $parser();

	$st = json_encode($res, JSON_UNESCAPED_UNICODE);
	if ($st) file_put_contents($cache, $st);

	return $st;
}

function get_page($url)
{
	$cache = '/tmp/cache_osm/'.md5($url).'.html';
	if (time() - @filemtime($cache) < 24*3600) return file_get_contents($cache);
	$st = file_get_contents($url);
	if ($st) file_put_contents($cache, $st);
	return $st;
}
