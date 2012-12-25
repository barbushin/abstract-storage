<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_MySQL extends Storage_KeyObject {

	protected $db;
	protected $table;
	protected $keyField;
	protected $quotedTable;
	protected $quotedKeyField;
	protected $allowedProperties;
	protected $unsetKeyField;

	public function __construct(Storage_MySQL $db, $table, array $allowedProperties = array(), $keyField = 'id') {
		$this->db = $db;
		$this->table = $table;
		$this->keyField = $keyField;
		$this->quotedTable = $db->quoteName($table);
		$this->quotedKeyField = $db->quoteName($keyField);
		$this->allowedProperties = $allowedProperties;
		$this->unsetKeyField = !$allowedProperties || !in_array($keyField, $allowedProperties);
		$this->setStorageName($db->getStorageName() . '.' . $table);
	}

	public function getDb() {
		return $this->db;
	}

	public function getTable() {
		return $this->table;
	}

	public function setPropertiesNames($propertiesNames) {
		if(!in_array($this->keyField, $propertiesNames)) {
			$propertiesNames[] = $this->keyField;
		}
		parent::setPropertiesNames($propertiesNames);
	}

	protected function handleStoreValue(&$object, $key = null) {
		parent::handleStoreValue($object, $key);
		foreach($object as $property => &$value) {
			if($this->allowedProperties && !in_array($property, $this->allowedProperties)) {
				throw new Storage_WrongRequest('Property "' . $property . '" is not allowed in ' . $this->getStorageName());
			}
		}
		$object[$this->keyField] = $key;
	}

	protected function handleStoredValue(&$object, $key) {
		parent::handleStoredValue($object, $key);
		if($this->unsetKeyField && isset($object[$this->keyField])) {
			unset($object[$this->keyField]);
		}
	}

	protected function getWhereSqlFromCriterias(Storage_EqualsCriteria $criteria) {
		$whereANDs = array();
		foreach($criteria->getPropertiesValues() as $property => $value) {
			$whereANDs[] = $this->db->quoteName($property) . ($value === '' || $value === null ? ' IS NULL' : '=' . $this->db->quote($value));
		}
		return $whereANDs ? ' WHERE ' . implode(' AND ', $whereANDs) : '';
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		return $this->db->fetchPreparedSql('SELECT * FROM ' . $this->quotedTable . $this->getWhereSqlFromCriterias($criteria), false, false, false, $this->keyField);
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable . $this->getWhereSqlFromCriterias($criteria));
	}

	protected function _get($key) {
		return $this->db->fetchPreparedSql('SELECT * FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->db->quote($key) . ' LIMIT 1', false, true);
	}

	protected function _gets(array $keys) {
		$keysValues = $this->db->fetchPreparedSql('SELECT * FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' IN (' . implode(', ', $this->db->quoteArray($keys)) . ')', false, false, false, $this->keyField);
		foreach($keys as $key) {
			if(!isset($keysValues[$key])) {
				$keysValues[$key] = null;
			}
		}
		return $keysValues;
	}

	protected function _set($key, $value) {
		$this->_sets(array($key => $value));
	}

	protected function _inserts($objects) {
		$inserts = array();
		foreach($objects as &$object) {
			if(isset($object[$this->keyField])) {
				unset($object[$this->keyField]);
			}
			$inserts[] = '(' . implode(', ', $this->db->quoteArray($object)) . ')';
		}
		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . implode(', ', $this->db->quoteNames(array_keys(reset($objects)))) . ')
		VALUES ' . implode(', ', $inserts));

		$firstInsertId = $this->db->getLastInsertId();
		return range($firstInsertId, $firstInsertId + count($objects) - 1);
	}

	protected function _sets(array $keysObjects) {
		$inserts = array();
		$fields = array();
		if($this->propertiesNames) {
			foreach($keysObjects as &$object) {
				$values = array();
				foreach($this->propertiesNames as $property) {
					$values[] = isset($object[$property]) ? $object[$property] : null;
				}
				$inserts[] = '(' . implode(', ', $this->db->quoteArray($values)) . ')';
			}
			$fields = $this->propertiesNames;
		}
		else {
			foreach($keysObjects as $object) {
				$inserts[] = '(' . implode(', ', $this->db->quoteArray($object)) . ')';
			}
			// TODO: check this extreme issue
			$fields = array_keys(reset($keysObjects));
		}
		$updates = array();
		foreach($fields as $field) {
			$updates[] = $this->db->quoteName($field) . ' = VALUES(' . $this->db->quoteName($field) . ')';
		}
		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . implode(', ', $this->db->quoteNames($fields)) . ')
		VALUES ' . implode(', ', $inserts) . '
		ON DUPLICATE KEY UPDATE ' . implode(', ', $updates));
	}

	protected function _increment($key, $property, $sum = 1) {
		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedKeyField . ', ' . $this->db->quoteName($property) . ')
		VALUES (' . $this->db->quote($key) . ', ' . $this->db->quote($sum) . ')
		ON DUPLICATE KEY UPDATE ' . $this->db->quoteName($property) . ' = ' . $this->db->quoteName($property) . ' + ' . $sum);
	}

	protected function _delete($key) {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->db->quote($key));
	}
}
