// https://josm.openstreetmap.de/wiki/Help/RemoteControlCommands
var josm = {
	version: false, // версия JOSM
	running: false, // запущен ли редактор

	/** иконка со ссылкой на объект */
	icon: function(id)
	{
		var type = osm.getType(id)

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
	link: function(a = {})
	{
		var type = osm.getType(a)
		if (!type) return null
		return 'http://127.0.0.1:8111/load_object?objects='+type[0]+a.id
	},
	/** ссылка на изменение параметров */
	link_edit: function(a, params)
	{
		var type = osm.getType(a)
		var i, tags = ''
		for (i in params) tags += (tags?'%7C':'')+i+'='+encodeURIComponent(params[i])
		return 'http://127.0.0.1:8111/load_object?objects='+type[0]+a.id+'&addtags='+tags
	},
	/** ссылка на создание точки */
	link_node: function(params)
	{
		var i, tags = ''
		for (i in params)
		if (i != 'lat' && i != 'lon')
			tags += (tags?'%7C':'')+i+'='+encodeURIComponent(params[i])
		return 'http://127.0.0.1:8111/add_node?lat='+params.lat+'&lon='+params.lon+'&addtags='+tags
	},
	link_zoom: function(a, delta=0.00001)
	{
		if (!a.lat && a.center)
			[a.lat, a.lon] = a.center
		return 'http://127.0.0.1:8111/load_and_zoom?left='+(a.lon-delta)+'&right='+(a.lon-0+delta)+'&top='+(a.lat-0+delta)+'&bottom='+(a.lat-delta)
	},
}

/** проверка запущен JOSM или нет */
setInterval(x=function(){
	fetch('http://127.0.0.1:8111/version').then(_=>_.json()).then(a=>{
		josm.running = false
		if (a.protocolversion)
		{
			josm.running = true
			josm.version = a.protocolversion.major+'.'+a.protocolversion.minor
		}
	}).catch(e=>{})
}, 10000)
setTimeout(x, 2000)

window.$(function() {
	var div = document.createElement('div')
	div.innerHTML = '<iframe name="josm" style="display: none"></iframe>'
	document.body.appendChild(div)
})
