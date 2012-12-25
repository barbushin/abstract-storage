<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_MySQL extends Storage_Abstract implements Storage_KeyValueProvider, Storage_KeyObjectProvider {

	const PRE_VALUE = '?';
	const PRE_VALUES = ',?';
	const PRE_NAME = '#';
	const PRE_NAMES = ',#';
	const PRE_EQUALS = ',=';
	const PRE_AS_IS = '$';

	protected static $persistantConnectionsHosts = array();

	protected $host;
	protected $hostPort;
	protected $login;
	protected $dbName;
	protected $connection;
	protected $connectionRetries;
	protected $transactionsStarted = 0;

	public function __construct($host, $login, $password, $dbName, Storage_Logger $logger = null, $persistent = true, $connectionRetries = array(0.1, 0.5, 1)) {

		if($persistent) {
			if(isset(self::$persistantConnectionsHosts[$host])) {
				if(self::$persistantConnectionsHosts[$host] >= 2) {
					throw new Exception('It\'s impossible to have more than 2 persistent MySQL connections to same host');
				}
				else {
					self::$persistantConnectionsHosts[$host]++;
					$this->hostPort = '3306';
				}
			}
			else {
				self::$persistantConnectionsHosts[$host] = 1;
			}
		}

		$this->host = $host;
		$this->dbName = $dbName;
		$this->login = $login;
		$this->password = $password;
		$this->persistent = $persistent;
		$this->connectionRetries = $connectionRetries;

		if($logger) {
			$this->setLogger($logger);
		}
		$this->setStorageName($this->host . '/' . $dbName);
	}

	protected function initConnection() {
		try {
			$hostOrIp = class_exists('HostsWrapper', true) ? HostsWrapper::forceIp($this->host) : $this->host;
			if($this->hostPort) {
				$hostOrIp .= ':' . $this->hostPort;
			}
			$connection = $this->persistent ? @mysql_pconnect($hostOrIp, $this->login, $this->password) : @mysql_connect($hostOrIp, $this->login, $this->password, true);
			if(!$connection) {
				throw new Storage_MySQL_ConnectionFailed('Could not connect to ' . $this->host . ' (' . $hostOrIp . '): ' . $this->getLastError());
			}
			if(!@mysql_selectdb($this->dbName, $connection)) {
				throw new Storage_MySQL_ConnectionFailed('Could not select DB "' . $this->dbName . '" on host "' . $this->host . '": ' . $this->getLastError());
			}
		}
		catch(Exception $exception) {
			if($this->connectionRetries) {
				usleep(array_shift($this->connectionRetries) * 1000000);
				return $this->initConnection();
			}
			else {
				throw $exception;
			}
		}
		return $connection;
	}

	public function getDbName() {
		return $this->dbName;
	}

	public function getUid() {
		return $this->host . '/' . $this->getDbName();
	}

	/**
	 * @param $name
	 * @return Storage_KeyValue
	 */
	public function getKeyValueCollection($name) {
		return new Storage_KeyValue_MySQL($this, $name);
	}

	/**
	 * @param $name
	 * @param array $propertiesNames
	 * @param string $keyField
	 * @return Storage_KeyObject_MySQL
	 */
	public function getKeyObjectCollection($name, array $propertiesNames = array(), $keyField = 'id') {
		return new Storage_KeyObject_MySQL($this, $name, $propertiesNames, $keyField);
	}

	public function insert($table, array $fieldsValues, $ignore = false) {
		$this->execPreparedQuery('INSERT' . ($ignore ? ' IGNORE' : '') . ' INTO ' . $this->quoteName($table) . ' (' . implode(', ', $this->quoteNames(array_keys($fieldsValues))) . ') VALUES (' . implode(', ', $this->quoteArray($fieldsValues)) . ')');
	}

	public function inserts($table, array $rows, $ignore = false, $fieldsNames = array()) {
		if($rows) {
			$bulks = array();
			foreach($rows as &$row) {
				$bulks[] = implode(', ', $this->quoteArray($row));
			}
			$this->execPreparedQuery('
			INSERT' . ($ignore ? ' IGNORE' : '') . '
			INTO ' . $this->quoteName($table) . ' (' . implode(',', $fieldsNames ? : $this->quoteNames(array_keys(reset($rows)))) . ')
			VALUES (' . implode('),(', $bulks) . ')');
		}
	}

	public function update($table, $fieldsValues, $filterFieldsValues) {
		$this->execPreparedQuery('UPDATE ' . $this->quoteName($table) . ' SET ' . $this->quoteEquals($fieldsValues, ', ') . ($filterFieldsValues ? ' WHERE ' . $this->quoteEquals($filterFieldsValues, ' AND ') : ''));
	}

	public function getLastInsertId() {
		return mysql_insert_id($this->getConnection());
	}

	public function setClientCharset($charset) {
		mysql_set_charset($charset, $this->getConnection());
	}

	public function execPreparedQuery($preparedSql, $isLogEnabled = true) {
		$isLogEnabled && $this->logger && $this->logStart();
		$preparedSql = str_replace("\n", '', $preparedSql);
		$result = @mysql_query($preparedSql, $this->getConnection());

		if(!$result) {
			$exception = new Storage_MySQL_QueryFailed('Storage_MySQL query: ' . $preparedSql . ' FAILED WITH ERROR: ' . $this->getLastError() . ' (' . mysql_errno($this->getConnection()) . ') in ' . $this->getStorageName());
			$isLogEnabled && $this->logCommit('query', func_get_args(), $exception);
			throw $exception;
		}
		$isLogEnabled && $this->logger && $this->logCommit('query', func_get_args());
		return $result;
	}

	protected function getLastError() {
		$connection = $this->getConnection(false);
		return $connection ? mysql_error($connection) : mysql_error();
	}

	public function query($prepareSql) {
		$replaces = array_slice(func_get_args(), 1);
		return $this->execPreparedQuery($this->prepareSql($prepareSql, $replaces));
	}

	public function fetchPreparedSql($preparedSql, $oneColumn = false, $oneRow = false, $keyValue = false, $idField = 'id') {
		$this->logger && $this->logStart();
		$fetched = $oneRow ? null : array();
		$result = $this->execPreparedQuery($preparedSql, false);
		if(($oneColumn && !$oneRow) || $keyValue) {
			while($row = mysql_fetch_row($result)) {
				if($keyValue) {
					$fetched[$row[0]] = $row[1];
				}
				else {
					$fetched[] = $row[0];
				}
			}
		}
		else {
			while($row = mysql_fetch_assoc($result)) {
				if($oneRow) {
					$fetched = $oneColumn ? reset($row) : $row;
					break;
				}
				elseif(isset($row[$idField])) {
					$fetched[$row[$idField]] = $row;
				}
				else {
					$fetched[] = $row;
				}
			}
		}
		$this->logger && $this->logCommit('query', func_get_args(), $fetched);
		return $fetched;
	}

	public function fetch($prepareSql) {
		$replaces = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replaces));
	}

	public function fetchRow($prepareSql) {
		$replaces = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replaces), false, true);
	}

	public function fetchColumn($prepareSql) {
		$replaces = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replaces), true, false);
	}

	public function fetchCell($prepareSql) {
		$replaces = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replaces), true, true);
	}

	public function fetchKeyValue($prepareSql) {
		$replaces = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replaces), false, false, true);
	}

	/**************************************************************
	QUOTERS
	 **************************************************************/

	public function quote($string, $withQuotes = true) {
		if((!is_scalar($string) && !is_null($string)) || (is_object($string) && !method_exists($string, '__toString'))) {
			throw new Storage_WrongRequest('Trying to quote "' . gettype($string) . '". Value: "' . var_export($string, true) . '"');
		}
		if(is_bool($string)) {
			$string = (int)$string;
		}
		elseif($string === '' || $string === null) {
			return 'NULL';
		}
		return $withQuotes ? '\'' . mysql_real_escape_string($string, $this->getConnection()) . '\'' : mysql_real_escape_string($string, $this->getConnection());
	}

	protected function getConnection($mustBeActive = true) {
		if(!$this->connection || !is_resource($this->connection)) {
			if($mustBeActive) {
				$this->connection = $this->initConnection();
			}
			else {
				return null;
			}
		}
		return $this->connection;
	}

	public function quoteArray(array $values, $withQuotes = true) {
		try {
			foreach($values as &$value) {
				$value = $this->quote($value, $withQuotes);
			}
		}
		catch(Storage_WrongRequest $exception) {
			throw new Storage_WrongRequest('Quoting array failed with error: ' . $exception->getMessage() . '. Given: "' . var_export($values, true) . '"');
		}
		return $values;
	}

	public function quoteName($name) {
		if(!is_scalar($name)) {
			throw new Storage_WrongRequest('Trying to quote "' . gettype($name) . '" as name. Value: "' . var_export($name, true) . '"');
		}
		if(!preg_match('/^[\d\w_]+$/', $name)) {
			throw new Storage_WrongRequest('Wrong name "' . $name . '" given to quote');
		}
		return '`' . $name . '`';
	}

	public function quoteNames(array $names) {
		foreach($names as &$name) {
			$name = $this->quoteName($name);
		}
		return $names;
	}

	public function quoteEquals(array $fieldsValues, $implode = false) {
		$equals = array();
		foreach($fieldsValues as $field => $value) {
			$equals[] = $this->quoteName($field) . ' = ' . $this->quote($value);
		}
		return $implode ? implode($implode, $equals) : $equals;
	}

	public function prepareSql($prepareSql, array $replaces = array()) {
		static $preRegexp;
		if(!$preRegexp) {
			$preRegexp = implode('|', array_map('preg_quote', array(self::PRE_VALUES, self::PRE_VALUE, self::PRE_NAMES, self::PRE_NAME, self::PRE_EQUALS, self::PRE_AS_IS)));
		}

		$splittedSql = preg_split('/(' . $preRegexp . ')/', $prepareSql, -1, PREG_SPLIT_DELIM_CAPTURE);
		if(count($replaces) != (count($splittedSql) - 1) / 2) {
			throw new Storage_MySQL_Exception('Count of replaces in prepare SQL "' . $prepareSql . '" mismatch');
		}
		if($replaces) {
			$preparedSql = '';
			foreach($splittedSql as $i => $p) {
				if($i % 2) {
					$pos = ($i - 1) / 2;
					if($p == self::PRE_VALUE || $p == self::PRE_NAME) {
						$p = $p == self::PRE_VALUE ? $this->quote($replaces[$pos]) : $this->quoteName($replaces[$pos]);
					}
					elseif($p == self::PRE_VALUES || $p == self::PRE_NAMES) {
						$p = implode(', ', $p == self::PRE_VALUES ? $this->quoteArray($replaces[$pos]) : $this->quoteNames($replaces[$pos]));
					}
					elseif($p == self::PRE_EQUALS) {
						$p = implode(', ', $this->quoteEquals($replaces[$pos]));
					}
					elseif($p == self::PRE_AS_IS) {
						$p = $replaces[$pos];
					}
				}
				$preparedSql .= $p;
			}
			return $preparedSql;
		}
		else {
			return $prepareSql;
		}
	}

	/**************************************************************
	TRANSACTIONS
	 **************************************************************/

	public function begin() {
		if(!$this->transactionsStarted) {
			$this->execPreparedQuery('BEGIN');
		}
		$this->transactionsStarted++;
		return $this;
	}

	public function commit() {
		if(!$this->transactionsStarted) {
			throw new Storage_MySQL_Exception('Trying to commit not existed transaction');
		}
		$this->transactionsStarted--;
		if(!$this->transactionsStarted) {
			$this->execPreparedQuery('COMMIT');
		}
		return $this;
	}

	public function rollback() {
		if(!$this->transactionsStarted) {
			throw new Storage_MySQL_Exception('Trying to rollback not existed transaction');
		}
		$this->transactionsStarted = 0;
		$this->execPreparedQuery('ROLLBACK');
		return $this;
	}

	public function __destruct() {
		if(!$this->persistent) {
			$connection = $this->getConnection(false);
			if($connection) {
				if($this->transactionsStarted) {
					$this->rollback();
				}
				mysql_close($connection);
			}
		}
	}
}

class Storage_MySQL_Exception extends Storage_Exception {

}

class Storage_MySQL_QueryFailed extends Storage_MySQL_Exception {

}

class Storage_MySQL_ConnectionFailed extends Storage_ConnectionFailed {

}
