<?php

class cache
{
//	public static function get($key, $default  =   "", $period = 'hour')
	public static function get($key, $callback = null, $period = 'hour')
	{
		if ($period == 'hour')  $period =       3600; else
		if ($period == 'day')   $period =    24*3600; else
		if ($period == 'week')  $period =  7*24*3600; else
		if ($period == 'month') $period = 30*24*3600;

		$fname = self::get_fname($key);

		if (time() - @filemtime($fname) < $period)
			return json_decode(file_get_contents($fname), true);

		$dir = dirname($fname);
		if (!file_exists($dir)) mkdir($dir, 0777, true);

		if (is_callable($callback))
			self::set($key, $value = is_callable($callback));
		else $value = $callback;

		return $value;
	}

	public static function set($key, $value)
	{
		$fname = self::get_fname($key);
		file_put_contents($fname, json_encode($value));
	}

	private static function get_fname($key)
	{
		if (strlen($key) > 16) $key = md5($key);
		return '/tmp/cache/'.$key;
	}
}
