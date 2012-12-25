<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 * Stores arrays with replacing items values from null/true/false to ''/1/0.
 * Arrays keys can be limited, but limits does not makes them required.
 */
abstract class Storage_KeyObject extends Storage_KeyValue implements Storage_Global {

	protected $propertiesNames = array();

	// TODO: MED add logging or realize logger as Decorator

	abstract protected function _find(Storage_EqualsCriteria $criteria);

	abstract protected function _deleteByCriteria(Storage_EqualsCriteria $criteria);

	function __construct(array $propertiesNames = array()) {
		$this->setPropertiesNames($propertiesNames);
	}

	public function setPropertiesNames($propertiesNames) {
		$this->propertiesNames = $propertiesNames;
	}

	protected function handleStoreValue(&$object, $key = null) {
		if(!is_array($object)) {
			throw new Storage_WrongRequest('Store data must be array, ' . gettype($object) . ' given');
		}
		if($this->propertiesNames && array_diff(array_keys($object), $this->propertiesNames)) {
			throw new Storage_WrongRequest('Data array contains keys that are not in properties list: ' . implode(', ', array_diff(array_keys($object), $this->propertiesNames)));
		}
		if($object) {
			foreach($object as &$val) {
				$this->handleScalarValue($val);
			}
		}
	}

	protected function handleStoredValue(&$object, $key) {
		if(!is_array($object)) {
			throw new Storage_WrongResponse('Storage returned "' . gettype($object) . '", array expected');
		}
	}

	public final function find(Storage_EqualsCriteria $criteria) {
		try {
			$this->logStart();
			$objects = $this->_find($criteria);
			$this->logCommit(__FUNCTION__, func_get_args(), $objects);
			if(static::HANDLE_STORE_DATA) {
				foreach($objects as $key => &$object) {
					$this->handleStoredValue($object, $key);
				}
			}
			return $objects;
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	public final function findOne(Storage_EqualsCriteria $criteria) {
		$objects = $this->find($criteria);
		return reset($objects);
	}

	// TODO: validate criteria is not empty to prevent developer mistake and DB dropping
	public final function deleteByCriteria(Storage_EqualsCriteria $criteria) {
		try {
			$this->logStart();
			$rollback = $this->isTransactional() ? new Transaction_Callback(function ($oldObjects, Storage_KeyObject $storage) {
				if($oldObjects) {
					$storage->sets($oldObjects);
				}
			}, array($this->find($criteria), $this)) : null;

			$this->_deleteByCriteria($criteria);

			if($rollback) {
				Transaction_Handler::getInstance()->addRollback($rollback);
			}
			$this->logCommit(__FUNCTION__, func_get_args());
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	public final function clear() {
		$this->deleteByCriteria(new Storage_EqualsCriteria());
	}

	public final function getAll() {
		return $this->find(new Storage_EqualsCriteria());
	}

	final public function increment($key, $property, $sum = 1) {
		try {
			$this->logStart();
			if(static::HANDLE_STORE_DATA) {
				if(!is_numeric($sum)) {
					throw new Storage_WrongRequest('Increment value must be numeric, "' . gettype($sum) . '" given: ');
				}
				$this->validateStoreKey($key);
			}
			if($sum) {
				$rollback = $this->isTransactional() ? new Transaction_Callback(function (Storage_KeyObject $storage) use ($key, $property, $sum) {
					$storage->increment($key, $property, $sum * -1);
				}, array($this)) : null;

				$this->_increment($key, $property, $sum);

				if($rollback) {
					Transaction_Handler::getInstance()->addRollback($rollback);
				}
				$this->logCommit(__FUNCTION__, func_get_args());
			}
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	protected function _increment($key, $property, $sum = 1) {
		if($this->propertiesNames && !in_array($property, $this->propertiesNames)) {
			throw new Storage_WrongRequest('Unknown property "' . $property . '"');
		}
		$object = $this->get($key);
		if(!$object) {
			$object = array($property => $sum);
		}
		elseif(!isset($object[$property])) {
			$object[$property] = $sum;
		}
		elseif(!is_numeric($object[$property])) {
			throw new Storage_WrongRequest('Stored value is not numeric and can\'t be incremented');
		}
		else {
			$object[$property] += $sum;
		}
		$this->set($key, $object);
	}
}
