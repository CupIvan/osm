<?php

define('URL', 'https://shtab.navalny.com');

$url = URL;
$fname = '/tmp/navalny.json';
if (time() - @filemtime($fname) < 24*3600)
	$url = $fname;
$st = file_get_contents($url);

if ($url == URL && $st) file_put_contents($fname, $st);

$st = preg_replace('#.+({"props.+?)</scr.+#s', '$1', $st);

$res = [];
$a = @json_decode($st, true);
if (!empty($a['props']['pageProps']['hqList']))
{
	foreach ($a['props']['pageProps']['hqList'] as $a)
	{
		$res[] = [
			'addr:region' => $a['region'],
			'addr:city'   => $a['city'],
			'address'     => $a['address'],
			'lat' => @$a['coordinates'][1],
			'lon' => @$a['coordinates'][0],
			'contact:website' => URL.'/hq/'.$a['slug'],
			'contact:phone'   => @$a['phone']?:'',
			'contact:email'   => @$a['contact_person_email']?:'',
			'person' => @$a['contact_person']?:'',
			'state'  => @$a['state']?:'',
			'opened' => true,
		];
	}
}

header('Content-type: application/json');
echo json_encode($res, JSON_UNESCAPED_UNICODE);
