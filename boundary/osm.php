<?php

/** формирование GeoJSON по данным из OSM */
function osm_make_boundary($id, $a, $is_simple = true)
{
	if (empty($a['elements'])) return false;

	$geo   = ['type'=>'MultiPolygon', 'coordinates'=>[]];
	$nodes = [];
	$ways  = [];
	$nodeFrom = []; // хэш: точка => начало линии
	$nodeTo   = []; // хэш: точка => конец линии

	foreach ($a['elements'] as $a)
	{
		if ($a['type'] == 'node') $nodes[$a['id']] = [$a['lon'], $a['lat']];
		if ($a['type'] == 'way')
		{
			$ways[$a['id']] = $a['nodes'];
			$nodeFrom[$a['nodes'][0]][] = $a['id'];
			$nodeTo[$a['nodes'][count($a['nodes'])-1]][] = $a['id'];
		}

		if ($a['id'] == $id)
		{
			$geo['id']   = $a['type'][0].$id;
			$geo['properties'] = $a['tags'];
			$refs = [];
			// внешние границы
			foreach ($a['members'] as $_)
			if ($_['type'] == 'way' && $_['role'] == 'outer')
				$refs[] = $_['ref'];
			// добавляем полигоны-дырки
			foreach ($a['members'] as $_)
			if ($_['type'] == 'way' && $_['role'] == 'inner')
				$refs[] = $_['ref'];

			// сортировка линий
			$sort_refs = function($refs) use($ways, $nodeFrom, $nodeTo)
			{
				$sorted = []; $ck = -1;

				while (count($refs))
				{
					$wayId = array_pop($refs);
					$sorted[++$ck] = [$wayId];
					$node = $ways[$wayId][0];

					// одна замкнутая линия?
					if ($node == $ways[$wayId][count($ways[$wayId])-1])
						continue;

					while (count($refs))
					{
						// ищем соседнюю
						foreach ($refs as $k => $id)
						{
							$last = count($ways[$id]) - 1;
							if ($ways[$id][0] == $node) $node = $ways[$id][$last];
							else
							if ($ways[$id][$last] == $node) $node = $ways[$id][0];
							else
								continue;
							$sorted[$ck][] = $id;
							unset($refs[$k]);
							continue 2;
						}

						// сначала проверим, не зациклились ли мы
						if ($node == $ways[$wayId][0])
						{
							print_r($ways[$wayId]);
							exit;
						}

						// если мы здесь, то значит полигон замкнулся,
						// поэтому начинаем всё заново для следующего
						break;
					}
				}
				return $sorted;
			};

			$lastNode = NULL;
			$refs = $sort_refs($refs);
			$geo['coordinates'] = osm_fill_coords($refs, $ways, $nodes, $is_simple);
			break;
		}
	}
	return $geo;
}

/** заполнение координат */
function osm_fill_coords($a, $ways, $nodes, $is_simple = true)
{
	foreach ($a as $i => $_)
		$a[$i] = [osm_fill_way_coords($_, $ways, $nodes, $is_simple)];
	return $a;
}

/** заполнение координат для массива линий */
function osm_fill_way_coords($a, $ways, $nodes, $is_simple = true)
{
	$res = []; $lastNode = NULL;
	static $cache = [];
	for ($i=0; $i<count($a); $i++)
	{
		$wayId = $a[$i];
		$w = $ways[$wayId];
		$xxx = osm_get_nodes($wayId, $ways, $nodes);
		if ($is_simple)
			$xxx = @$cache[$wayId] ?: $cache[$wayId] = osm_simple_nodes($xxx);

		// проверяем нужно ли переворачивать первую линию
		if (is_null($lastNode))
		{
			if (empty($a[$i+1])) $is_reverse = false;
			else
			{
				$nextWay = $ways[$a[$i+1]];
				$lastNode = $w[0];
				$is_reverse = $nextWay[0] == $lastNode || $nextWay[count($nextWay)-1] == $lastNode;
			}
		}
		else
			$is_reverse = $w[0] != $lastNode;

		if ($is_reverse)
			$xxx = array_reverse($xxx);

		$lastNode = $w[$is_reverse ? 0 : count($w)-1];
		array_pop($xxx); // убираем последнюю точку, чтобы не дублировалась
		$res = array_merge($res, $xxx);
	}
	return $res;
}

/** список точек линии */
function osm_get_nodes($wayId, $ways, $nodes)
{
	$res = [];
	$a = $ways[$wayId];
	for ($i = 0; $i < count($a)-1; $i++)
	if (!empty($nodes[$a[$i]]))
		$res[] = $nodes[$a[$i]];
	else
	{
		echo "w$wayId n{$a[$i]}\n";
		exit;
	}
	return $res;
}


/** получение отношения из OSM */
function osm_get_relation($id, $is_full = false)
{
	$fname = CACHE_DIR."/$id.json";
	if (time() - @filemtime($fname) < 24*3600)
		$st = file_get_contents($fname);
	else
	{
		if ($is_full) $id .= '/full';
		$st = file_get_contents("https://www.openstreetmap.org/api/0.6/relation/$id.json");
		if ($st) file_put_contents($fname, $st);
	}
	return json_decode($st, true);
}

/** список регионов из отношения */
function osm_get_subareas($a)
{
	if (empty($a['elements'])) return [];
	$res = [];
	foreach ($a['elements'] as $a)
		foreach ($a['members'] as $a)
		if ($a['role'] == 'subarea')
			$res[] = $a['ref'];
	return $res;
}

/** упрощение ломанной */
function osm_simple_nodes($a)
{
	// для каждой точки высчитываем площадь треугольника, относительно соседних вершин
	// и выкидываем точку с минимальной площадью
	while (true)
	{
		$s_min = null; $s_i = 0; $s_n = 0;
		for ($i=1; $i<count($a)-1; $i++)
		{
			$s = osm_calc_square($a[$i-1], $a[$i], $a[$i+1]);
			if ($s > 1) continue;
			if (is_null($s_min) || $s < $s_min) { $s_min = $s; $s_i = $i; $s_n++; }
		}
		if (!$s_n) break;
		array_splice($a, $s_i, 1); // удаляем эту точку
		if (count($a) < 10) break;
	}

	return $a;
}

/** площадь треугольника по координатам x2 */
// https://m.fxyz.ru/4/279/280/302/904/
function osm_calc_square($x, $y, $z)
{
	$x1 = $x[0]; $y1 = $x[1];
	$x2 = $y[0]; $y2 = $y[1];
	$x3 = $z[0]; $y3 = $z[1];
	$s = ($x1-$x3)*($y2-$y3) - ($y1-$y3)*($x2-$x3);
	return abs($s);
}
