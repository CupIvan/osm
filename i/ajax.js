function ajax(url, handler)
{
	var xhr = new XMLHttpRequest()
	xhr.open('GET', url, true)
	xhr.send()
	xhr.onreadystatechange = function(){
		if (xhr.readyState != 4) return
		if (xhr.status == 200)
			handler(JSON.parse(xhr.responseText))
	}
}
