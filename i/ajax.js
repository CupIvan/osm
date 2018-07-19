function ajax(url, handler, post)
{
	var xhr = new XMLHttpRequest()
	if (!post)
	{
		xhr.open('GET', url, true)
		xhr.send()
	}
	else
	if (post.tagName == 'FORM')
	{
		xhr.open('POST', url, true)
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		var i, st = ''
		for (i = 0; i < post.elements.length; i++)
		if (post.elements[i].name)
		if (post.elements[i].value)
			st += '&'+post.elements[i].name+'='+encodeURIComponent(post.elements[i].value)
		xhr.send(st)
	}
	xhr.onreadystatechange = function(){
		if (xhr.readyState != 4) return
		if (xhr.status == 200)
			handler(JSON.parse(xhr.responseText))
	}
}
