<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Optimized extends Storage_KeyValue implements Storage_Global {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	/**
	 * @var Storage_KeyValue|Storage_Global
	 */
	protected $storage;

	protected $storageKeysValues;
	protected $sets = array();
	protected $increments = array();
	protected $alwaysGetAll;
	protected $incrementsAsSets;
	protected $areChangesSynced = true;

	public function __construct(Storage_KeyValue $baseStorage, $alwaysGetAll = false, $incrementsAsSets = false) {
		$this->storage = $baseStorage;
		$this->alwaysGetAll = $alwaysGetAll;
		$this->incrementsAsSets = $incrementsAsSets;
		if($alwaysGetAll) {
			$this->validateGlobalInterface();
		}
	}

	protected function _get($key) {
		// TODO: optimize
		$values = $this->_gets(array($key));
		return $values ? reset($values) : null;
	}

	protected function _gets(array $keys) {
		$keysValues = array();
		// get from sets
		foreach($keys as $i => $key) {
			if(isset($this->sets[$key])) {
				$keysValues[$key] = $this->sets[$key];
				unset($keys[$i]);
			}
		}
		// get from cached gets
		foreach($keys as $i => $key) {
			if(isset($this->storageKeysValues[$key])) {
				$keysValues[$key] = $this->storageKeysValues[$key];
				unset($keys[$i]);
			}
		}
		// get from storage
		if($keys) {
			if($this->alwaysGetAll) {
				if($this->storageKeysValues === null) {
					$this->storageKeysValues = $this->storage->getAll();
					foreach($keys as $key) {
						$keysValues[$key] = array_key_exists($key, $this->storageKeysValues) ? $this->storageKeysValues[$key] : null;
					}
				}
			}
			else {
				foreach($this->storage->gets($keys) as $key => $value) {
					$this->storageKeysValues[$key] = $value;
					$keysValues[$key] = $value;
				}
			}
		}
		// increment existed values
		foreach($keysValues as $key => &$value) {
			if(isset($this->increments[$key])) {
				$value += $this->increments[$key];
			}
		}
		return $keysValues;
	}

	protected function _set($key, $value) {
		if(isset($this->increments[$key])) {
			$value += $this->increments[$key];
			unset($this->increments[$key]);
		}
		$this->sets[$key] = $value;
		$this->areChangesSynced = false;
	}

	protected function _delete($key) {
		if(isset($this->increments[$key])) {
			unset($this->increments[$key]);
		}
		$this->sets[$key] = null;
		$this->storageKeysValues[$key] = null;
		$this->areChangesSynced = false;
	}

	protected function validateGlobalInterface() {
		if(!$this->storage instanceof Storage_Global) {
			throw new Exception('Storage "' . $this->storage->getStorageName() . '" does not implements Storage_Global interface');
		}
	}

	public function getAll() {
		$this->validateGlobalInterface();
		if($this->alwaysGetAll) {
			if($this->storageKeysValues === null) {
				$this->storageKeysValues = $this->storage->getAll();
			}
		}
		else {
			$this->storageKeysValues = $this->storage->getAll();
		}
		return $this->_gets(array_keys($this->storageKeysValues));
	}

	public function clear() {
		$this->validateGlobalInterface();
		$this->storageKeysValues = array();
		$this->sets = array();
		$this->increments = array();
		$this->storage->clear();
		$this->areChangesSynced = true;
	}

	protected function _increment($key, $sum) {
		if(isset($this->sets[$key])) {
			$this->sets[$key] += $sum;
		}
		else {
			if(isset($this->increments[$key])) {
				$this->increments[$key] += $sum;
			}
			else {
				$this->increments[$key] = $sum;
			}
		}
		$this->areChangesSynced = false;
	}

	public function syncChangesWithStorage() {
		if(!$this->areChangesSynced) {
			if($this->incrementsAsSets) {
				foreach($this->_gets(array_keys($this->increments)) as $key => $value) {
					$this->sets[$key] = $value;
				}
			}
			else {
				$this->storage->increments($this->increments);
			}
			foreach($this->sets as $key => $value) {
				if($value === null) {
					$this->storage->delete($key);
					unset($this->sets[$key]);
				}
			}
			$this->storage->sets($this->sets);
			$this->sets = array();
			$this->increments = array();
			$this->areChangesSynced = true;
		}
	}

	public function __destruct() {
		$this->syncChangesWithStorage();
	}
}
