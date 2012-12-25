<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_Filtered extends Storage_KeyObject_Proxy {

	const HANDLE_STORE_DATA = true;
	const IS_TRANSACTIONAL = false;

	/** @var Storage_EqualsCriteria  */
	protected $defaultCriteria;

	protected $hiddenProperties;

	public function __construct(Storage_KeyObject $storage, Storage_EqualsCriteria $defaultCriteria, $defaultPropertiesValues = array(), $hiddenProperties = array()) {
		parent::__construct($storage);

		$this->defaultCriteria = $defaultCriteria;
		$this->defaultPropertiesValues = $defaultPropertiesValues;

		if(array_diff($hiddenProperties, array_keys($defaultPropertiesValues))) {
			throw new Exception('Hidden properties must be also in default properties list');
		}
		$this->hiddenProperties = $hiddenProperties;
	}

	protected function applyDefaultCriteria(Storage_EqualsCriteria $criteria) {
		foreach($this->defaultCriteria->getPropertiesValues() as $property => $value) {
			$criteria->addEqualCriteria($property, $value);
		}
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		$this->applyDefaultCriteria($criteria);
		return parent::_find($criteria);
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		$this->applyDefaultCriteria($criteria);
		parent::_deleteByCriteria($criteria);
	}

	protected function handleStoreValue(&$object, $key = null) {
		foreach($this->defaultPropertiesValues as $property => $value) {
			$object[$property] = $value;
		}
	}

	protected function handleStoredValue(&$object, $key) {
		if(!$this->isObjectMatchToDefaultCriteria($object)) {
			$object = null;
		}
		else {
			foreach($this->hiddenProperties as $property) {
				if(isset($object[$property])) {
					unset($object[$property]);
				}
			}
		}
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		$object = parent::_get($key);
		return $object !== null && $this->isObjectMatchToDefaultCriteria($object) ? $object : null;
	}

	protected function isObjectMatchToDefaultCriteria($object) {
		if(!$this->defaultCriteria) {
			return true;
		}
		foreach($this->defaultCriteria->getPropertiesValues() as $property => $value) {
			if(!array_key_exists($property, $object) || $object[$property] != $value) {
				return false;
			}
		}
		return true;
	}

	protected function _delete($key) {
		$object = parent::_get($key);
		if($object && $this->isObjectMatchToDefaultCriteria($object)) {
			parent::_delete($key);
		}
	}

	protected function _increment($key, $property, $sum = 1) {
		$object = parent::_get($key);
		if($object && $this->isObjectMatchToDefaultCriteria($object)) {
			$this->storage->increment($key, $property, $sum);
		}
	}
}

