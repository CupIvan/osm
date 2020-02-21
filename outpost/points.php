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
}

function get_points($brand, $parser)
{
	$cache = './cache/'.$_REQUEST['brand'].'.json';
	if (time() - @filemtime($cache) < 7*24*3600) return file_get_contents($cache);

	$res = $parser();

	$st = json_encode($res, JSON_UNESCAPED_UNICODE);
	if ($st) file_put_contents($cache, $st);

	return $st;
}

function get_page($url)
{
	$cache = './cache/'.md5($url).'.html';
	if (time() - @filemtime($cache) < 24*3600) return file_get_contents($cache);
	$st = file_get_contents($url);
	if ($st) file_put_contents($cache, $st);
	return $st;
}
