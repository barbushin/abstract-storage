<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_MySQLFieldPerKey extends Storage_KeyValue implements Storage_Global {

	/**
	 * @var Storage_MySQL
	 */
	protected $db;
	protected $table;
	protected $keyField;
	protected $keyValue;
	protected $allowedKeys;
	protected $quotedTable;
	protected $quotedKeyField;
	protected $quotedKeyValue;
	protected $quotedAllowedKeys;

	public function __construct(Storage_MySQL $db, $table, $keyValue, array $allowedKeys, $keyField = 'id') {
		if(!$allowedKeys) {
			throw new Exception('Argument $allowedKeys must be not empty');
		}
		$this->db = $db;
		$this->table = $table;
		$this->keyField = $keyField;
		$this->keyValue = $keyValue;
		$this->allowedKeys = $allowedKeys;
		$this->quotedTable = $db->quoteName($table);
		$this->quotedKeyField = $db->quoteName($keyField);
		$this->quotedKeyValue = $db->quote($keyValue);
		$this->quotedAllowedKeys = $db->quoteNames($allowedKeys);
	}

	public function getDb() {
		return $this->db;
	}

	public function getTable() {
		return $this->table;
	}

	protected function validateStoreKey($key) {
		parent::validateStoreKey($key);
		if(!in_array($key, $this->allowedKeys)) {
			throw new Storage_WrongRequest('Unknown key "' . $key . '"');
		}
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return $this->db->fetchPreparedSql('SELECT ' . $this->db->quoteName($key) . ' FROM ' . $this->quotedTable . ' ' . $this->sqlWhere(), true, true);
	}

	protected function _inserts($values) {
		throw new Storage_Exception('This storage does not support insert() operation');
	}

	protected function _increment($key, $sum) {
		$quotedKey = $this->db->quoteName($key);
		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedKeyField . ', ' . $quotedKey . ')
		VALUES (' . $this->quotedKeyValue . ', ' . $this->db->quote($sum) . ')
		ON DUPLICATE KEY UPDATE ' . $quotedKey . ' = ' . $quotedKey . ' + ' . $sum);
	}

	protected function _gets(array $keys) {
		$row = $this->db->fetchPreparedSql('SELECT ' . implode(', ', $this->db->quoteNames($keys)) . ' FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->quotedKeyValue, false, true);
		if($row) {
			if(!in_array($this->keyField, $this->allowedKeys)) {
				unset($row[$this->keyField]);
			}
			return $row;
		}
		else {
			return array();
		}
	}

	protected function _set($key, $value) {
		$this->_sets(array($key => $value));
	}

	protected function _sets(array $keysValues) {
		$updates = array();
		foreach($keysValues as $key => $value) {
			$updates[] = $this->db->quoteName($key) . ' = VALUES(' . $this->db->quoteName($key) . ')';
		}
		$keysValues[$this->keyField] = $this->keyValue;
		$this->db->execPreparedQuery('
			INSERT INTO ' . $this->quotedTable . ' (' . implode(', ', $this->db->quoteNames(array_keys($keysValues))) . ')
			VALUES (' . implode(', ', $this->db->quoteArray($keysValues)) . ')
			ON DUPLICATE KEY UPDATE ' . implode(', ', $updates));
	}

	protected function _delete($key) {
		$this->db->execPreparedQuery('UPDATE ' . $this->quotedTable . ' SET ' . $this->db->quoteName($key) . ' = NULL ' . $this->sqlWhere());
	}

	public function getAll() {
		$row = $this->db->fetchPreparedSql('SELECT ' . ($this->quotedAllowedKeys ? implode(', ', $this->quotedAllowedKeys) : '*') . ' FROM ' . $this->quotedTable . ' ' . $this->sqlWhere(), false, true, false, $this->keyField);
		if(!$row) {
			foreach($this->allowedKeys as $key) {
				$row[$key] = null;
			}
		}
		elseif(!in_array($this->keyField, $this->allowedKeys)) {
			unset($row[$this->keyField]);
		}
		return $row;
	}

	public function clear() {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable . ' ' . $this->sqlWhere());
	}

	protected function sqlWhere() {
		return 'WHERE ' . $this->quotedKeyField . ' = ' . $this->quotedKeyValue . ' LIMIT 1';
	}
}
