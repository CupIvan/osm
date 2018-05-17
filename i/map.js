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

	L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>',
		maxZoom: 20,
		maxNativeZoom: 18,
	}).addTo(map)

	map.on('popupopen', function(e){ if (make_popup) e.popup.setContent(make_popup(e.popup.options.data)) })

	if (params.update_hash !== false)
	map.on('moveend', function(){
		var hash = '?z='+map.getZoom()
		var a = map.getCenter()
		hash += '&lat='+a.lat+'&lon='+a.lng
		hash = '#'+(Math.round(a.lat*a.lat/10+a.lng*a.lng/10))+'/'+hash
		history.replaceState(null, null, hash);

		// сохраняем также в localStorage
		localStorage.setItem('lat', a.lat)
		localStorage.setItem('lon', a.lng)
		localStorage.setItem('z',   map.getZoom())
	})
	return map
}
}
