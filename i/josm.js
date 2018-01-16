var josm = {
	/** иконка со ссылкой на объект */
	icon: function(id, type)
	{
		if (!type) type = josm.getType(id)

		var url = 'http://www.openstreetmap.org/browse/'+type+'/'+id

		var pic = ''
		if (type == 'node')     pic = 'b/b5/Mf_node'
		if (type == 'way')      pic = '8/83/Mf_area'
		if (type == 'relation') pic = '5/59/Relation'

		return '<a href="'+url+'" target="_blank" title="открыть на openstreetmap.org">' +
			'<img valign="absmiddle" src="http://wiki.openstreetmap.org/w/images/' + pic + '.png"/>'+
			'</a>'
	},
	/** ссылка на объект */
	link: function(id, type)
	{
		if (!type) type = josm.getType(id)
		return 'http://www.openstreetmap.org/browse/'+type+'/'+id
	},
	/** тип объекта */
	getType: function(id)
	{
		var type; id += ''

		if (id.charAt(0) == 'n') type = 'node'
		if (id.charAt(0) == 'w') type = 'way'
		if (id.charAt(0) == 'r') type = 'relation'

		id = id.replace(/\D/g, '')
		if (!type) type = 'node'
		return type
	},
}
