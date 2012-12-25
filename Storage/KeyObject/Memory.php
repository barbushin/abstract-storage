<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_Memory extends Storage_KeyObject {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	protected $objects;
	protected $flushDataCallback;
	protected $isChanged;

	function __construct($objects = array(), $propertiesNames = array(), $flushDataCallback = null) {
		$this->objects = $objects;
		if($flushDataCallback) {
			$this->setFlushDataCallback($flushDataCallback);
		}
		parent::__construct($propertiesNames);
	}

	public function setFlushDataCallback($callback) {
		if(!is_callable($callback)) {
			throw new Exception('Argument $callback is not callable');
		}
		if($this->flushDataCallback) {
			throw new Exception('Flush callback already defined');
		}
		$this->flushDataCallback = $callback;
	}

	public function isChanged() {
		return $this->isChanged;
	}

	public function flushObjectsChanged() {
		$this->isChanged = false;
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		$objects = $this->objects;
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
		foreach($this->_find($criteria) as $id => $object) {
			unset($this->objects[$id]);
		}
		$this->isChanged = true;
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return isset($this->objects[$key]) ? $this->objects[$key] : null;
	}

	protected function _set($key, $object) {
		$this->objects[$key] = $object;
		$this->isChanged = true;
	}

	protected function _delete($key) {
		if(isset($this->objects[$key])) {
			unset($this->objects[$key]);
			$this->isChanged = true;
		}
	}

	public function flushIsChanged() {
		$this->isChanged = false;
	}

	public function __destruct() {
		if($this->isChanged && $this->flushDataCallback) {
			$this->flushIsChanged();
			call_user_func($this->flushDataCallback, $this);
		}
	}
}
