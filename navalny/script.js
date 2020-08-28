function _hours(x)
{
	x = x.toLowerCase()
	const a={'понедельник': 'пн', 'вторник': 'вт', 'среда': 'ср', 'четверг': 'чт', 'пятница': 'пт', 'суббота': 'сб', 'воскресенье': 'вс',
		'птн': 'пт', 'c': '',
		'пн':'Mo','вт':'Tu','ср':'We','чт':'Th','пт':'Fr','сб':'Sa','вс':'Su'}
	for (let day in a) x = x.replace(new RegExp(day, 'i'), a[day])
	x = x.replace('открыт', '')
	x = x.replace('работает', '')
	x = x.replace('часы работы', '')
	x = x.replace(': ', ' ')
	x = x.replace(/–/g, '-')
	x = x.replace(/с /g, '')
	x = x.replace(/до/g, '-')
	x = x.replace(/\s+-\s+/g, '-')
	x = x.replace(/,/g, ';')
	x = x.replace(/\s+/g, ' ')
	x = x.replace(/^\s+|\s+$|\./g, '')
	return x
}
