var map = {
init: function(p){
	var H = p || {};
	var i, a, t = document.location.hash.replace(/.+?\?/, '').split('&');
	for (i = 0; i < t.length; i++) { a = t[i].split('='); H[a[0]] = a[1]; }

	// или пробуем загрузить последние координаты
	if (!H[i='lat'] && (t=localStorage.getItem(i))) H[i] = t
	if (!H[i='lon'] && (t=localStorage.getItem(i))) H[i] = t
	if (!H[i='z']   && (t=localStorage.getItem(i))) H[i] = t

	var map = L.map('map').setView([H.lat||53.814, H.lon||55.679], H.z||5)

	if (!H.lat && document.location.protocol == 'https:') map.locate()

	L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>',
		maxZoom: 20,
		maxNativeZoom: 18,
	}).addTo(map)

	map.on('popupopen', function(e){ e.popup.setContent(make_popup(e.popup.options.data)) })

	if (p.update_hash === false)
	map.on('moveend', function(){
		var hash = '?z='+map.getZoom()
		var a = map.getCenter()
		hash += '&lat='+a.lat+'&lon='+a.lng
		hash = '#'+(Math.round(a.lat*a.lat/10+a.lng*a.lng/10))+'/'+hash
		document.location = hash

		// сохраняем также в localStorage
		localStorage.setItem('lat', a.lat)
		localStorage.setItem('lon', a.lng)
		localStorage.setItem('z',   map.getZoom())
	})
	return map
}
}
