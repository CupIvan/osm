/**
 * Функция подстановки окончаний
 * @author CupIvan <mail@cupivan.ru>
 * @example n=12; st=n+' стул'+ok(n, '', 'а', 'ьев')
 * @example ok('12 стул(|а|ьев)')
 */
function ok(n, n1, n2, n5)
{
	if (n1 == undefined) return ok_string(n)

	if (n5 == undefined) n5 = n2||''
	res = n5; d = n % 10; dd = n % 100
	if (d < 5) res = n2||''
	if (d < 2) res = n1||''
	if (!d || (dd > 4 && dd < 21)) res = n5
	return res
}

function ok_string(st)
{
	var n = /\d+/.exec(st)[0]
	st = st.replace(/\((.+?)\)/g, (_,a)=>{
		var a = a.split('|')
		return ok(n, ...a)
	})
	return st
}
