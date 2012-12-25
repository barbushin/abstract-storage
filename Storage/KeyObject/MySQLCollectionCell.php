<?php

/*

CREATE TABLE `key_object_collection_in_cell` (
  `id` varchar(255) NOT NULL,
  `data` varbinary(65550) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

 */

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_MySQLCollectionCell extends Storage_KeyObject {

	const DATA_GZIP_LEVEL = 7;

	/**
	 * @var Storage_MySQL
	 */
	protected $db;
	protected $table;
	protected $dataRowKey;
	protected $keyField;
	protected $dataField;
	protected $quotedTable;
	protected $quotedKeyField;
	protected $quotedDataField;
	protected $gzipData;

	protected static $globalObjects = array();

	/**
	 * @var Storage_KeyObject_Memory
	 */
	protected $objectsMemoryStorage;

	public function __construct(Storage_MySQL $db, $table, $dataRowKey, $gzipData = false, $keyField = 'id', $dataField = 'data') {
		$this->db = $db;
		$this->table = $table;
		$this->keyField = $keyField;
		$this->gzipData = $gzipData;
		$this->dataField = $dataField;
		$this->dataRowKey = $dataRowKey;
		$this->quotedTable = $db->quoteName($table);
		$this->quotedKeyField = $db->quoteName($keyField);
		$this->quotedDataField = $db->quoteName($dataField);
		$this->quotedDataRowKey = $db->quote($dataRowKey);
		$this->setStorageName($db->getStorageName() . '.' . $table . '[' . $keyField . '=' . $dataRowKey . ']');
		$this->initObjects();
	}

	public function getDb() {
		return $this->db;
	}

	public function getTable() {
		return $this->table;
	}

	protected function initObjects() {
		$storageId = $this->getStorageName();
		if(!isset(self::$globalObjects[$storageId])) {
			$objects = array();
			$objectsJson = $this->db->fetchPreparedSql('SELECT ' . $this->quotedDataField . ' FROM ' . $this->quotedTable . ' WHERE ' . $this->keyField . ' = ' . $this->quotedDataRowKey, true, true, false, $this->keyField);
			if($objectsJson) {
				if($this->gzipData) {
					$objectsJson = gzuncompress($objectsJson);
					if(!$objectsJson) {
						throw new Storage_Exception('Wrong store GZIP data in ' . $this->getStorageName());
					}
				}
				$objects = @json_decode($objectsJson, true);
				if($objects === false) {
					throw new Storage_Exception('Wrong store JSON data in ' . $this->getStorageName());
				}
			}
			self::$globalObjects[$storageId] = new Storage_KeyObject_Memory($objects);
		}
		$this->objectsMemoryStorage = self::$globalObjects[$this->getStorageName()];
	}

	protected function saveObjects() {
		if($this->objectsMemoryStorage->isChanged()) {
			$objectsJson = json_encode($this->objectsMemoryStorage->getAll(), JSON_NUMERIC_CHECK);
			if($this->gzipData) {
				$objectsJson = gzcompress($objectsJson, static::DATA_GZIP_LEVEL);
			}
			$this->db->execPreparedQuery('
			INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedKeyField . ', ' . $this->quotedDataField . ')
			VALUES (' . $this->quotedDataRowKey . ', ' . $this->db->quote($objectsJson) . ')
			ON DUPLICATE KEY UPDATE ' . $this->quotedDataField . ' = VALUES(' . $this->quotedDataField . ')');
			$this->objectsMemoryStorage->flushObjectsChanged();
		}
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		return $this->objectsMemoryStorage->find($criteria);
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		$this->objectsMemoryStorage->deleteByCriteria($criteria);
	}

	protected function _get($key) {
		return $this->objectsMemoryStorage->get($key);
	}

	protected function _set($key, $value) {
		$this->objectsMemoryStorage->set($key, $value);
	}

	protected function _delete($key) {
		$this->objectsMemoryStorage->delete($key);
	}

	public function __destruct() {
		$this->saveObjects();
	}
}
