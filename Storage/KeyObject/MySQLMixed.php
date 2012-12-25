<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_MySQLMixed extends Storage_KeyObject {

	const DATA_GZIP_LEVEL = 7;

	protected $db;
	protected $table;
	protected $keyField;
	protected $mixedField;
	protected $quotedTable;
	protected $quotedKeyField;
	protected $quotedMixedField;
	protected $quotedTableFields;
	protected $fixedProperties;
	protected $gzipMixedData;
	protected $unsetKeyField;
	protected $isNumericKeys;

	public function __construct(Storage_MySQL $db, $table, array $fixedProperties = array(), $keyField = 'id', $isNumericKeys = true, $unsetKeyField = false, $mixedField = 'data', $gzipMixedData = false) {
		$this->db = $db;
		$this->table = $table;
		$this->keyField = $keyField;
		$this->isNumericKeys = $isNumericKeys;
		$this->mixedField = $mixedField;
		$this->fixedProperties = $fixedProperties;
		$this->quotedTable = $db->quoteName($table);
		$this->quotedKeyField = $db->quoteName($keyField);
		$this->quotedMixedField = $db->quoteName($mixedField);
		$this->quotedFixedProperties = $this->db->quoteNames($fixedProperties);
		$this->tableFields = array_merge(array($keyField, $mixedField), $this->fixedProperties);
		$this->quotedTableFields = array_merge($db->quoteNames(array($keyField, $mixedField)), $this->quotedFixedProperties);
		$this->gzipMixedData = $gzipMixedData;
		$this->unsetKeyField = $unsetKeyField;
		$this->setStorageName($db->getStorageName() . '.' . $table);
	}

	public function getDb() {
		return $this->db;
	}

	public function getTable() {
		return $this->table;
	}

	protected function handleStoreValue(&$object, $key = null) {
		parent::handleStoreValue($object, $key);
		$mixedPropertiesValues = array();
		foreach($object as $property => $value) {
			if(!in_array($property, $this->fixedProperties)) {
				$mixedPropertiesValues[$property] = $value;
				unset($object[$property]);
			}
		}
		if($mixedPropertiesValues) {
			$object[$this->mixedField] = $this->gzipMixedData ? gzcompress(json_encode($mixedPropertiesValues), static::DATA_GZIP_LEVEL) : json_encode($mixedPropertiesValues);
		}
		else {
			$object[$this->mixedField] = null;
		}
		$object[$this->keyField] = $key;
	}

	protected function handleStoredValue(&$object, $key) {
		parent::handleStoredValue($object, $key);

		if($this->unsetKeyField && isset($object[$this->keyField])) {
			unset($object[$this->keyField]);
		}

		if(array_key_exists($this->mixedField, $object)) {
			$mixedPropertiesValues = $object[$this->mixedField];
			if($mixedPropertiesValues) {
				if($this->gzipMixedData) {
					$mixedPropertiesValues = @gzuncompress($mixedPropertiesValues);
					if($mixedPropertiesValues === false) {
						throw new Storage_Exception('Unzip field "' . $this->mixedField . '" of row with id "' . $key . '" failed in storage: ' . $this->getStorageName());
					}
				}

				$mixedPropertiesValues = @json_decode($mixedPropertiesValues);
				if($mixedPropertiesValues === false) {
					throw new Storage_Exception('JSON decode of(unzipped?) field "' . $this->mixedField . '" of row with id "' . $key . '" failed in storage: ' . $this->getStorageName());
				}

				unset($object[$this->mixedField]);
				foreach($mixedPropertiesValues as $property => $value) {
					if(empty($object[$property])) {
						$object[$property] = $value;
					}
				}
			}
		}
	}

	protected function getWhereSqlFromCriterias(Storage_EqualsCriteria $criteria) {
		$whereANDs = array();
		foreach($criteria->getPropertiesValues() as $property => $value) {
			if(!in_array($property, $this->fixedProperties)) {
				throw new Storage_WrongRequest('Storage filters supports only fixed fields');
			}
			$whereANDs[] = $this->db->quoteName($property) . ($value === '' || $value === null ? ' IS NULL' : '=' . $this->db->quote($value));
		}
		return $whereANDs ? ' WHERE ' . implode(' AND ', $whereANDs) : '';
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		return $this->db->fetchPreparedSql('SELECT ' . implode(', ', $this->quotedTableFields) . ' FROM ' . $this->quotedTable . $this->getWhereSqlFromCriterias($criteria), false, false, false, $this->keyField);
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable . $this->getWhereSqlFromCriterias($criteria));
	}

	protected function _get($key) {
		return $this->db->fetchPreparedSql('SELECT ' . implode(', ', $this->quotedTableFields) . ' FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->db->quote($key) . ' LIMIT 1', false, true);
	}

	protected function _gets(array $keys) {
		$keysValues = $keys ? $this->db->fetchPreparedSql('SELECT ' . implode(', ', $this->quotedTableFields) . ' FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' IN (' . implode(', ', $this->db->quoteArray($keys)) . ')', false, false, false, $this->keyField) : array();
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
		$newNotNumericKeys = array();
		$inserts = array();
		foreach($objects as &$object) {
			if(!$this->isNumericKeys) {
				$newNotNumericKeys[] = $object[$this->keyField] = $this->generateNewKey();
			}
			elseif(isset($object[$this->keyField])) {
				unset($object[$this->keyField]);
			}
			$inserts[] = '(' . implode(', ', $this->db->quoteArray($object)) . ')';
		}

		$this->db->execPreparedQuery('
		INSERT INTO ' . $this->quotedTable . ' (' . implode(', ', $this->db->quoteNames(array_keys(reset($objects)))) . ')
		VALUES ' . implode(', ', $inserts));

		if($newNotNumericKeys) {
			return $newNotNumericKeys;
		}
		else {
			$firstInsertId = $this->db->getLastInsertId();
			return range($firstInsertId, $firstInsertId + count($objects) - 1);
		}
	}

	protected function _sets(array $keysObjects) {
		$inserts = array();
		foreach($keysObjects as $object) {
			$values = array();
			foreach($this->tableFields as $property) {
				$values[] = $this->db->quote(array_key_exists($property, $object) ? $object[$property] : null);
			}
			$inserts[] = '(' . implode(', ', $values) . ')';
		}

		$updates = array();
		foreach($this->quotedTableFields as $quotedProperty) {
			$updates[] = $quotedProperty . ' = VALUES(' . $quotedProperty . ')';
		}

		$this->db->execPreparedQuery('
			INSERT INTO ' . $this->quotedTable . ' (' . implode(', ', $this->quotedTableFields) . ')
			VALUES ' . implode(', ', $inserts) . '
			ON DUPLICATE KEY UPDATE ' . implode(', ', $updates));
	}

	protected function _increment($key, $property, $sum = 1) {
		if(in_array($property, $this->fixedProperties)) {
			$this->db->execPreparedQuery('
				INSERT INTO ' . $this->quotedTable . ' (' . $this->quotedKeyField . ', ' . $this->db->quoteName($property) . ')
				VALUES (' . $this->db->quote($key) . ', ' . $this->db->quote($sum) . ')
				ON DUPLICATE KEY UPDATE ' . $this->db->quoteName($property) . ' = ' . $this->db->quoteName($property) . ' + ' . $sum);
		}
		else {
			parent::_increment($key, $property, $sum);
		}
	}

	protected function _delete($key) {
		$this->db->execPreparedQuery('DELETE FROM ' . $this->quotedTable . ' WHERE ' . $this->quotedKeyField . ' = ' . $this->db->quote($key));
	}
}
