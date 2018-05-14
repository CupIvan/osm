var osm = {
search_region: 60, // коэффициент округления координат (чем меньше - тем больший регион скачивается)

search: function(filter, handler){
	var bounds = ''
	if (filter.bounds)
	{
		filter.bounds, k = osm.search_region
		bounds = ''
			+ '(' + Math.floor(filter.bounds[1]*k)/k
			+ ',' + Math.floor(filter.bounds[0]*k)/k
			+ ',' + Math.ceil( filter.bounds[3]*k)/k
			+ ',' + Math.ceil( filter.bounds[2]*k)/k
			+ ')';
		delete filter.bounds
	}

	var type = {}
	if (filter[x='node'])     { type[x]=1; delete filter[x] }
	if (filter[x='way'])      { type[x]=1; delete filter[x] }
	if (filter[x='relation']) { type[x]=1; delete filter[x] }
	if (!type.node && !type.way && !type.relation) type = {node:1}

	var i, f=''
	for (i in filter)
	{
		f = '"'+i+'"'
		if (filter[i] !== true)
			f += filter[i] ? '="'+filter[i]+'"' : ''
	}

	var query = ''
		+'[out:json];('
			+(type.node    ?('node['+f+']'+bounds+';'):'')
			+(type.way     ?('way[' +f+']'+bounds+';'):'')
			+(type.relation?('rel[' +f+']'+bounds+';'):'')
		+');(._;>;);out body;';

	ajax.load('/overpass/?query='+encodeURIComponent(query), function(x){
		var i, j, a, data = []
		var nodes = ways = {}

		if (!x.elements) return data

		for (i = 0; i < x.elements.length; i++)
		{
			a = x.elements[i]
			if (a.tags)
			for (j in filter)
			if (a.tags[j])
			if (filter[j] === true || (filter[j] && a.tags[j] == filter[j]))
			{
				data.push(a)
				break
			}

			if (a.type == 'node') nodes[a.id] = a; else
			if (a.type == 'way')  ways [a.id] = a
		}

		var _coords = function(a){
			var i, id, res = []
			for (i=0; i<a.length; i++)
			{
				id = (typeof(a[i]) == 'object') ? a[i].id : a[i]
				if (nodes[id])
					res.push([ nodes[id].lon, nodes[id].lat ])
			}
			return res
		}

		// создаём список точек для геометрии
		for (a=data[i=0]; i<data.length; a=data[++i])
		{
			data[i].geoJSON = []
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
				} else
				if (a.members[j].role == 'part')
				{
					ref = a.members[j].ref
					if (!ways[ref]) continue

					data[i].geoJSON.push(_coords(ways[ref].nodes))
					x = x.concat(ways[ref].nodes)
				}

				data[i].nodes = x
			}
			if (a.type == 'way' || a.type == 'relation')
			{
				x=[]; data[i].center=[0, 0]
				for (j=0; j<data[i].nodes.length; j++)
				if (nodes[data[i].nodes[j]])
				{
					x.push(nodes[data[i].nodes[j]])
					data[i].center[0] += nodes[data[i].nodes[j]].lat
					data[i].center[1] += nodes[data[i].nodes[j]].lon
				}
				data[i].nodes = x
				data[i].center[0] /= x.length
				data[i].center[1] /= x.length

				data[i].geo = _coords(data[i].nodes)
				data[i].geoJSON.push(data[i].geo)
			}
			if (a.type == 'node')
				data[i].center = [a.lat, a.lon]

			if (a.type == 'node')
				data[i].geoJSON = {type: 'Point', coordinates: [a.lat, a.lon]}
			else
				data[i].geoJSON = {type: 'MultiPolygon', coordinates: [data[i].geoJSON]}
		}

		handler(data)
	})
}

}
