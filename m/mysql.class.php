<?php

class mysql
{
	static $connection = null;
	static $encoding   = 'utf8mb4_unicode_ci';
	static $encoding_  = 'utf8mb4_unicode_ci'; // предыдущая кодировка

	static $host = '127.0.0.1';
	static $user = 'root';
	static $pass = '';
	static $base = '';

	static $history = array();
	static $errors  = array();
	static $error   = '';
	static $cache   = false;
	static $cache_sql = array();

	static $data = null;
	static $count = 0;

	static function setEncoding($enc)
	{
		self::$encoding_= self::$encoding;
		self::connect();
		mysqli_set_charset(self::$connection, str_replace('utf-8', 'utf8', self::$encoding = $enc));
	}
	static function restoreEncoding()
	{
		self::setEncoding(self::$encoding_);
	}


	static function connect()
	{
		if (self::$connection !== null) return true;
		self::$connection = @mysqli_connect(self::$host, self::$user, self::$pass);
		if (!self::$connection)
			return false;
		if (!mysqli_select_db(self::$connection, self::$base))
		{
			$errno = mysqli_errno(self::$connection);
			if ($errno) array_push(self::$errors, $errno."\n".mysqli_error(self::$connection));
			return false;
		}
		self::setEncoding(self::$encoding);
		return true;
	}
	static function prepare_callback($x){ // use ($data) {
		$data = $GLOBALS['prepare_data'];
		$ind  =&$GLOBALS['prepare_index'];
		if ($x['field']) { $x['key'] = $x['field']; $x['eq'] = true; }
		$name = $x['key'];
		$eq   = $x['eq'] ? "`$name` = " : '';

		$value = '';

		if (is_null($data)) $GLOBALS['prepare_skip'] = true;
		else
		if (is_scalar($data)) $value = $data;
		else
		if (is_array($data))
		{
			if ($name)
				if (isset($data[$name])) $value = $data[$name];
				else
					$GLOBALS['prepare_skip'] = true;
			else
			if ($x['type'] != 'as' && isset($data[$ind]) && $data[$ind] !== NULL)  $value = $data[$ind++];
			else
			if (empty($name)) $value = $data;
			else
				$GLOBALS['prepare_skip'] = true;
		}
		else
		if ($data instanceOf Model && $x['type'] != 'kv')
		{
			if (is_null($value = $data->$name))
				$GLOBALS['prepare_skip'] = true;
		}

		if ($GLOBALS['prepare_skip']) return '';

		switch($x['type'])
		{
			case 'p': return $eq.$value;
			case 'i': return $eq.(int)$value;
			case 'f': return $eq.(float)$value;
			case 's':
			case 'S':
//				if ($value === '') $GLOBALS['prepare_skip'] = true;
				if ($x['type'] == 'S') { $value = "%$value%"; $eq = str_replace('=', 'LIKE', $eq); }
				return $eq.self::prepareString($value);
			case 'd':  return date('"Y-m-d"', is_numeric($value) && $value > 3000 ? $value : strtotime($value));
			case 'dt': return date('"Y-m-d H:i:s"', is_numeric($value) && $value > 3000 ? $value : strtotime($value));
			case 'ai': case 'as':
				if (empty($value)) { $GLOBALS['prepare_skip'] = true; return ''; }
				$a = array();
				if (is_scalar($value)) $value = explode(',', $value);
				foreach ($value as $v)
					if ($x['type'] == 'ai')
					{
						if (is_numeric($v)) $a[] = $v;
					}
					else
					if ($x['type'] == 'as')
					{
						$a[] = self::prepareString($v);
					}
				return str_replace('=', 'IN', $eq).implode(',', $a);
			case 'kv': case 'kvf':
				$st = ''; $glue = ($x['type'] == 'kvf') ? ' AND ' : ', ';
				if ($value instanceOf Model) $value = $value->getData();
				else
				if ($data instanceOf Model) $value = $data->getData();

				if (is_array($value))
				foreach ($value as $k => $v)
				{
					$st .= ($st?$glue:'');
					if (is_array($v)) // задан диапазон значений
					{
						if (count($v) == 2 && is_numeric($v[0]) && is_numeric($v[1]))
						$st .= "`$k` >= ".self::prepareString($v[0])." AND `$k` < ".self::prepareString($v[1]);
						else
						$st .= self::prepare("`$k` IN(?as)", $v);
					}
					else
					{
						$st .= strpos($v, '%') !== false && $GLOBALS['prepare_is_select'] ? "`$k` LIKE " : "`$k` = ";
						if (is_bool($v)) $v = $v ? 1 : 0;
						if (strpos($v, '=') === 0) $st .= substr($v, 1); // COMMENT: например '= NOW()'
						else
//						if (is_numeric($v)) $st .= $v;
//						else
						if (is_null($v))    $st .= 'NULL';
						else $st .= self::prepareString($v);
					}
				}
				if (!$st) $st = 1;
				return $st;
			default:
				return '';
		}
	}
	static function prepareString($st)
	{
		return '"'.addslashes($st).'"';
	}
	static function prepare($sql, $data='_stored', $d2='_stored', $d3=NULL, $d4=NULL, $d5=NULL, $d6=NULL)
	{
		$sql = "\n$sql ";
		if ($data === '_stored') $data = self::$data;

		if ($d2 !== '_stored')
			$data = array($data, $d2, $d3, $d4, $d5, $d6);

		self::$data = $data;

		// FIXME: убрать глобальные переменные
		$GLOBALS['prepare_is_select'] = stripos(" $sql", 'SELECT');
		$GLOBALS['prepare_data']  = $data;
		$GLOBALS['prepare_index'] = 0;
		$GLOBALS['prepare_skip']  = false;

		$sql = preg_replace_callback('#((?<field>[a-z0-9_]+)=)?\?(?<type>s|S|i|f|dt|d|p|ai|as|kvf|kv):?(?<key>[a-z0-9_]*)(?<eq>=?)#i',
			'mysql::prepare_callback', $sql);
		if (@$GLOBALS['prepare_skip']) $sql = '';

		unset($GLOBALS['prepare_is_select']);
		unset($GLOBALS['prepare_data']);
		unset($GLOBALS['prepare_index']);
		unset($GLOBALS['prepare_skip']);

		return $sql;
	}
	static function query($sql)
	{
		$t = microtime(true);
		$sql_dump = mb_strlen($sql) > 5*1024 ? mb_substr($sql, 0, 5*1024 - 3).'...' : $sql;
		self::connect();
		$res = @mysqli_query(self::$connection, $sql);
		$errno = mysqli_errno(self::$connection);
		if ($errno)
		{
			array_push(self::$errors, self::$error = $errno."\n".mysqli_error(self::$connection)."\n".$sql_dump);
			//log::mysql('error', [$sql, self::$error]);
		}
		array_push(self::$history, $sql_dump.' -- '.$t.' + '.round(microtime(true)-$t, 4));
		return $errno ? false : $res;
	}
	static function fetch($res)
	{
		return mysqli_fetch_assoc($res);
	}
	static function getList($sql)
	{
		$is_sql_count = false;
		if (strpos($sql, 'SELECT') !== false && strpos($sql, 'LIMIT') !== false)
		{
			$sql = preg_replace('/SELECT /', 'SELECT SQL_CALC_FOUND_ROWS ', $sql, 1);
			$is_sql_count = true;
		}

		$res = self::query($sql);
		if (!$res) return array();
		$ret = array();
		while ($row = mysqli_fetch_assoc($res))
			$ret[] = $row;
		self::$count = $is_sql_count ? (int)self::getValue('SELECT FOUND_ROWS()') : count($ret);
		return $ret;
	}
	static function getItem($sql)
	{
		if (self::$cache && isset($cache_sql[$sql])) return $cache_sql[$sql];
		$res = self::query($sql);
		if ($res === false) return array();
		$res = mysqli_fetch_assoc($res); if (is_null($res)) $res = array();
		if (self::$cache) $cache_sql[$sql] = $res;
		return $res;
	}
	static function getValue($sql)
	{
		if (self::$cache && isset($cache_sql[$sql])) return $cache_sql[$sql];
		$res = self::query($sql);
		if (!is_object($res)) return NULL;
		$res = mysqli_fetch_array($res);
		if (empty($res)) return NULL;
		$res = $res[0];
		if (self::$cache) $cache_sql[$sql] = $res;
		return $res;
	}
	static function getValues($sql, $field = null)
	{
		$res = self::getList($sql);
		if (is_null($field)) $field = array_keys($res[0])[0];
		foreach ($res as $i => $a)
			$res[$i] = $a[$field];
		return $res;
	}
	static function getListCount()
	{
		return self::$count;
	}
	static function get($table, $id, $fields = NULL)
	{
		if (empty($fields)) $fields = '*';
		$p = is_numeric($id) ? '`id` = ?i' : '?p';
		$sql = mysql::prepare("SELECT $fields FROM `$table` WHERE $p LIMIT 1", $id);
		return mysql::getItem($sql);
	}
	static function search($table, $a, $fields = NULL, $orderBy = NULL)
	{
		if ( empty($fields))  $fields = '*';
		if (!empty($orderBy)) $orderBy = "ORDER BY $orderBy";
		$sql = mysql::prepare("SELECT $fields FROM `$table` WHERE ?kvf $orderBy", $a);
		return mysql::getList($sql);
	}
	static function insert($table, $a)
	{
		$sql = self::prepare("INSERT INTO `$table` SET ?kv", $a);
		$res = mysql::query($sql);
		return mysqli_errno(self::$connection) ? false : mysql::getLastInsertId();
	}
	static function insert_values($table, $a)
	{
		if (empty($a)) return false;
		$values = [];
		foreach ($a as $a) $values[] = mysql::prepare('(?as)', $a);
		$sql = self::prepare("INSERT INTO `$table` VALUES ".implode(',', $values));
		$res = mysql::query($sql);
		return mysqli_errno(self::$connection) ? false : mysql::getLastInsertId();
	}
	static function getLastInsertId() { return mysqli_insert_id(self::$connection); }
	static function insert_duplicate($table, $a)
	{
		$sql = self::prepare("INSERT INTO `$table` SET ?kv ON DUPLICATE KEY UPDATE ?kv", $a, $a);
		$res = mysql::query($sql);
		return mysqli_errno(self::$connection) ? false : self::getRowsCount();
	}
	static function replace($table, $a)
	{
		$sql = self::prepare("REPLACE INTO `$table` SET ?kv", $a);
		$res = mysql::query($sql);
		return mysqli_errno(self::$connection) ? false : true;
	}
	static function update($table, $a, $where = NULL)
	{
		// передали id записи, которую нужно изменить
		if (is_numeric($where))
		{
			$a['id'] = $where;
			$where   = NULL;
		}
		// передали условие выборки записей на изменение
		if (is_null($where))
		{
			if (empty($a['id'])) return NULL;
			$where = self::prepare("`id` = ?i", $a['id']);
			unset($a['id']);
		}
		// $where в качестве хеша
		if (is_array($where))
		{
			$where = mysql::prepare('?kvf', $where);
		}
		if (empty($a)) return 0;
		$sql = self::prepare("UPDATE `$table` SET ?kv WHERE ?p", $a, $where);
		$res = self::query($sql);
		if ($res === false) return false;
		return self::getRowsCount();
	}
	static function delete($table, $sql)
	{
		if (empty($sql)) return false;
		if (is_array($sql)) $sql = mysql::prepare('?kvf', $sql);
		if (is_numeric($sql)) $sql = mysql::prepare("`id` = ?i", $sql); // передали число
		$sql = self::prepare("DELETE FROM `$table` WHERE ?p", $sql);
		return self::query($sql);
	}
	/** кол-во изменённых рядов */
	static function getRowsCount()
	{
		return mysqli_affected_rows(self::$connection);
	}
	static function getLastError()
	{
		$errno = mysqli_errno(self::$connection);
		return $errno ? array('errno' => $errno) : array();
	}
	static function setDefault($value, &$a, $fields='')
	{
		$x = $a;
		if (!empty($fields)) $x = array_intersect_key($a, array_fill_keys(explode(',', $fields), 1));
		foreach ($x as $k => $v)
		{
			if (empty($v)) $a[$k] = $value;
		}
	}
}
