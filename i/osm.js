var osm = {

search: function(filter, handler){
	var bounds = filter.bounds, k = 60; // 60 - коэффициент округления координат (чем меньше - тем больший регион скачивается)
	bounds = Math.floor(bounds[1]*k)/k+','+Math.floor(bounds[0]*k)/k+','+
		Math.ceil(bounds[3]*k)/k+','+Math.ceil(bounds[2]*k)/k;
	delete filter.bounds

	var i, f=''
	for (i in filter) f=i // FIXME: сделать правильно

	var query = ''
		+'[out:json];('
			+'node["'+f+'"]('+bounds+');'
			+'way["'+f+'"]('+bounds+');'
			+'rel["'+f+'"]('+bounds+');'
		+');(._;>;);out body;';

	ajax.load('/overpass.php?query='+encodeURIComponent(query), function(x){
		var i, j, a, res = []
		var nodes = ways = {}

		if (!x.elements) return res

		for (i = 0; i < x.elements.length; i++)
		{
			a = x.elements[i]
			if (a.tags)
			for (j in filter)
			if (a.tags[j])
			{
				res.push(a)
				break
			}

			if (a.type == 'node') nodes[a.id] = a; else
			if (a.type == 'way')  ways[a.id]  = a
		}

		// создаём список точек для геометрии
		for (a=res[i=0]; i<res.length; a=res[++i])
		{
			if (a.type == 'relation')
			{
				var ref, k, last_node = 0
				var x = []
				for (j = 0; j < a.members.length; j++)
				if (a.members[j].role == 'outer')
				{
					ref = a.members[j].ref
					if (!ways[ref]) continue

					// если это первый вэй - смотрим нужно ли повернуть, чтобы стыковался со следующим
					if (!last_node) // COMMENT: не переворачиваем, если первой ноды нет в следующем вее (значит там последняя)
					if (a.members[j+1] && ways[a.members[j+1].ref])
					if (ways[a.members[j+1].ref].nodes.indexOf(ways[ref].nodes[0]) == -1)
						last_node = ways[ref].nodes[0] // COMMENT: так мы показали, что переворачивать не нужно
					// определяем, нужно ли повернуть линию или нет
					if (ways[ref].nodes[0] != last_node)
						ways[ref].nodes.reverse()
					for (k = 0; k < ways[ref].nodes.length; k++)
					{
						last_node = ways[ref].nodes[k]
						if (x.indexOf(last_node) == -1)
							x.push(last_node)
					}
				}
				res[i].nodes = x
			}
			if (a.type == 'way' || a.type == 'relation')
			{
				res[i].geo = []
				for (j=0; j<res[i].nodes.length; j++)
				if (nodes[res[i].nodes[j]])
					res[i].geo.push([
						nodes[res[i].nodes[j]].lat,
						nodes[res[i].nodes[j]].lon,
					])
			}
		}

		handler(res)
	})
}

}
