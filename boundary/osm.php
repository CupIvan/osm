<?php

/** формирование GeoJSON по данным из OSM */
function osm_make_boundary($id, $a)
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
			foreach ($a['members'] as $a)
			if ($a['type'] == 'way' && $a['role'] == 'outer')
				$refs[] = $a['ref'];

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
//			print_r($refs);
			$geo['coordinates'] = osm_fill_coords($refs, $ways, $nodes);
			break;
		}
	}
	return $geo;
}

/** заполнение координат */
function osm_fill_coords($a, $ways, $nodes)
{
	foreach ($a as $i => $_)
		$a[$i] = [osm_fill_way_coords($_, $ways, $nodes)];
	return $a;
}

/** заполнение координат для массива линий */
function osm_fill_way_coords($a, $ways, $nodes)
{
	$res = []; $lastNode = NULL;
	for ($i=0; $i<count($a); $i++)
	{
		$wayId = $a[$i];
		$w = $ways[$wayId];
		$xxx = osm_get_nodes($wayId, $ways, $nodes);

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
	for ($i = 0; $i < count($a); $i++)
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
