<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_MemoryShared extends Storage_KeyObject {

	protected static $objects;
	protected static $isChanged;

	public function __construct($objects = array(), $propertiesNames = array()) {
		$this->_sets($objects);
		parent::__construct($propertiesNames);
	}

	public function isChanged() {
		return self::$isChanged;
	}

	public function flushObjectsChanged() {
		self::$isChanged = false;
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		$objects = self::$objects;
		foreach($criteria->getPropertiesValues() as $property => $value) {
			foreach($objects as $key => $object) {
				if(!isset($object[$property]) || $object[$property] != $value) {
					unset($objects[$key]);
				}
			}
		}
		return $objects;
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		foreach($this->find($criteria) as $id => $object) {
			unset(self::$objects[$id]);
		}
		self::$isChanged = true;
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return isset(self::$objects[$key]) ? self::$objects[$key] : null;
	}

	protected function _set($key, $object) {
		self::$objects[$key] = $object;
		self::$isChanged = true;
	}

	protected function _delete($key) {
		if(isset(self::$objects[$key])) {
			unset(self::$objects[$key]);
			self::$isChanged = true;
		}
	}
}
