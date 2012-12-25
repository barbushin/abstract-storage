<?php

/*

CREATE TABLE `key_value_numeric` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

 */

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_MySQLNumericKeys extends Storage_KeyValue implements Storage_Global {

	const JSON_VALUE_PREFIX = '%JSON%';

	/**
	 * @var Storage_MySQL
	 */
	protected $db;
	protected $table;
	protected $keyField;
	protected $valueField;
	protected $quotedTable;
	protected $quotedKeyField;
	protected $quotedValueField;

	public function __construct(Storage_MySQL $db, $table, $keyField = 'id', $valueField = 'value', Storage_Logger $logger = null) {
		$this->db = $db;
		$this->table = $table;
		$this->keyField = $keyField;
		$this->valueField = $valueField;
		$this->quotedTable = $db->quoteName($table);
		$this->quotedKeyField = $db->quoteName($keyField);
		$this->quotedValueField = $db->quoteName($valueField);
		if($logger) {
			$this->setLogger($logger);
		}
	}

	public function getDb() {
		return $this->db;
	}

	public function getTable() {
		return $this->table;
	}

	public function getKeyField() {
		return $this->keyField;
	}

	public function getValueField() {
		return $this->valueField;
	}

	protected function handleStoredValue(&$value, $key) {
		if(strpos($value, static::JSON_VALUE_PREFIX) === 0) {
			$value = json_decode(substr($value, strlen(static::JSON_VALUE_PREFIX)), true);
		}
		parent::handleStoredValue($value, $key);
	}

	protected function handleStoreValue(&$value, $key = null) {
		parent::handleStoreValue($value, $key);
		if(is_array($value)) {
			$value = static::JSON_VALUE_PREFIX . json_encode($value);
		}
	}

	public function generateNewKey() {
		throw new Exception('Unable to generate key for this kind of storage');
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return $this->db->fetchPreparedSql('SELECT ' . $this->quotedValueField . ' FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->db->quote($key) . ' LIMIT 1', true, true);
	}

	protected function _increment($key, $sum) {
		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedKeyField . ', ' . $this->quotedValueField . ')
		VALUES (' . $this->db->quote($key) . ', ' . $this->db->quote($sum) . ')
		ON DUPLICATE KEY UPDATE ' . $this->quotedValueField . ' = ' . $this->quotedValueField . ' + ' . $sum);
	}

	protected function validateStoreKey($key) {
		parent::validateStoreKey($key);
		if(strlen($key) > 255) {
			throw new Storage_WrongRequest('Key length cannot be longer than 255 chars');
		}
	}

	protected function _gets(array $keys) {
		return $this->db->fetchPreparedSql('SELECT ' . $this->quotedKeyField . ', ' . $this->quotedValueField . ' FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' IN (' . implode(', ', $this->db->quoteArray($keys)) . ')', false, false, true, $this->keyField);
	}

	protected function _inserts($values) {
		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedValueField . ')
		VALUES (' . implode('), (', $this->db->quoteArray($values)) . ')');
		$firstInsertId = $this->db->getLastInsertId();
		return range($firstInsertId, $firstInsertId + count($values) - 1);
	}

	protected function _set($key, $value) {
		$this->_sets(array($key => $value));
	}

	protected function _sets(array $keysValues) {
		if($keysValues) {
			$bulkInserts = array();
			foreach($keysValues as $key => $value) {
				$bulkInserts[] = '(' . $this->db->quote($key) . ', ' . $this->db->quote($value) . ')';
			}
			$this->db->execPreparedQuery('
			INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedKeyField . ', ' . $this->quotedValueField . ')
			VALUES ' . implode(',', $bulkInserts) . '
			ON DUPLICATE KEY UPDATE ' . $this->quotedValueField . ' = VALUES(' . $this->quotedValueField . ')');
		}
	}

	protected function _delete($key) {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->db->quote($key));
	}

	protected function _mDelete(array $keys) {
		if($keys) {
			$this->db->query('DELETE FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' IN (,?)', $keys);
		}
	}

	public function getAll() {
		return $this->db->fetchPreparedSql('SELECT ' . $this->quotedKeyField . ', ' . $this->quotedValueField . ' FROM ' . $this->quotedTable, false, false, true, $this->keyField);
	}

	public function clear() {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable);
	}
}
