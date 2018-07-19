if (!window.L)
{
	document.write(''
		+'<l'+'ink rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css"'
			+'integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==" crossorigin=""/>'
		+'<s'+'cript src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js"'
			+'integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw==" crossorigin=""></scrip'+'t>'
	)
}

var map = {
init: function(params){
	params = params || {};
	var i, a, t = document.location.hash.replace(/.+?\?/, '').split('&');
	for (i = 0; i < t.length; i++) { a = t[i].split('='); params[a[0]] = a[1]; }

	// или пробуем загрузить последние координаты
	if (!params[i='lat'] && (t=localStorage.getItem(i))) params[i] = t
	if (!params[i='lon'] && (t=localStorage.getItem(i))) params[i] = t
	if (!params[i='z']   && (t=localStorage.getItem(i))) params[i] = t

	var map = L.map(params.id||'map').setView([params.lat||55.74, params.lon||37.62], params.z||11)

	if (!params.lat && document.location.protocol == 'https:') map.locate()

	L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a>',
		maxZoom: 20,
		maxNativeZoom: 18,
	}).addTo(map)

	map.on('popupopen', function(e){ if (window.make_popup != undefined) e.popup.setContent(window.make_popup(e.popup.options.data)) })

	map.on('moveend', function(){
		var hash = '?z='+map.getZoom()
		var a = map.getCenter()

		// обновляем хэш
		if (params.update_hash !== false)
		{
			hash += '&lat='+a.lat+'&lon='+a.lng
			hash = '#'+(Math.round(a.lat*a.lat/10+a.lng*a.lng/10))+'/'+hash
			history.replaceState(null, null, hash);
		}

		// сохраняем также в localStorage
		localStorage.setItem('lat', a.lat)
		localStorage.setItem('lon', a.lng)
		localStorage.setItem('z',   map.getZoom())
	})
	return map
}
}
