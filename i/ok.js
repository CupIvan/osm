/**
 * Функция подстановки окончаний
 * @author CupIvan <mail@cupivan.ru>
 * @example n=12; st=n+' стул'+ok(n, '', 'а', 'ьев');
 */
function ok(n, n1, n2, n5)
{
	if (!n5) n5 = n2||''
	res = n5; d = n % 10; dd = n % 100
	if (d < 5) res = n2||''
	if (d < 2) res = n1||''
	if (!d || (dd > 4 && dd < 21)) res = n5
	return res
}
