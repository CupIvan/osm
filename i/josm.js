var josm = {
	version: false, // версия JOSM
	running: false, // запущен ли редактор

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
	link: function(a)
	{
		var type = a.type
		if (!type) type = osm.getType(a.id)
		return 'http://127.0.0.1:8111/load_object?objects='+type[0]+a.id
	},
}

/** проверка запущен JOSM или нет */
setInterval(x=function(){
	if (window.ajax)
	window.ajax('http://127.0.0.1:8111/version', function(a){
		josm.running = false
		if (a.protocolversion)
		{
			josm.running = true
			josm.version = a.protocolversion.major+'.'+a.protocolversion.minor
		}
	})
}, 10000)
x()

window.$(function() {
	var div = document.createElement('div')
	div.innerHTML = '<iframe name="josm" style="display: none"></iframe>'
	document.body.appendChild(div)
})
