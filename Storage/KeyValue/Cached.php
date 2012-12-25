<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Cached extends Storage_KeyValue implements Storage_Global {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	/**
	 * @var Storage_KeyValue|Storage_Global
	 */
	protected $baseStorage;

	/**
	 * @var Storage_KeyValue|Storage_Global
	 */
	protected $cacheStorage;

	protected $baseStorageFullSyncMode;
	protected $cacheStoragePassiveMode;

	/**
	 * @param Storage_KeyValue $baseStorage
	 * @param Storage_KeyValue $cacheStorage
	 * @param bool $baseStorageFullSyncMode all data saving will be synced to base storage
	 * @param bool $cacheStoragePassiveMode disable saving to cache storage
	 */
	public function __construct(Storage_KeyValue $baseStorage, Storage_KeyValue $cacheStorage, $baseStorageFullSyncMode = false, $cacheStoragePassiveMode = false) {
		$this->baseStorage = $baseStorage;
		$this->cacheStorage = $cacheStorage;
		$this->baseStorageFullSyncMode = $baseStorageFullSyncMode;
		$this->cacheStoragePassiveMode = $cacheStoragePassiveMode;
	}

	public function getBaseStorage() {
		return $this->baseStorage;
	}

	public function getCacheStorage() {
		return $this->cacheStorage;
	}

	protected function _get($key) {
		$result = $this->cacheStorage->get($key);
		if($result === null) {
			$result = $this->baseStorage->get($key);
			if($result !== null && !$this->cacheStoragePassiveMode) {
				$this->cacheStorage->set($key, $result);
			}
		}
		return $result;
	}

	protected function _gets(array $keys) {
		$keysValues = $this->cacheStorage->gets($keys);
		$getFromBaseKeys = array();
		foreach($keysValues as $key => $value) {
			if($value === null) {
				$getFromBaseKeys[] = $key;
			}
		}
		if($getFromBaseKeys) {
			$newKeysValues = $this->baseStorage->gets($getFromBaseKeys);
			foreach($newKeysValues as $key => $value) {
				if($value === null) {
					unset($newKeysValues[$key]);
				}
			}
			if(!$this->cacheStoragePassiveMode) {
				$this->cacheStorage->sets($newKeysValues);
			}
			foreach($newKeysValues as $key => $value) {
				$keysValues[$key] = $value;
			}
		}
		return $keysValues;
	}

	protected function _set($key, $value) {
		if($this->baseStorageFullSyncMode || $this->cacheStoragePassiveMode) {
			$this->baseStorage->set($key, $value);
		}
		if($this->cacheStoragePassiveMode) {
			$this->cacheStorage->delete($key);
		}
		else {
			$this->cacheStorage->set($key, $value);
		}
	}

	protected function _sets(array $keysValues) {
		if($this->baseStorageFullSyncMode || $this->cacheStoragePassiveMode) {
			$this->baseStorage->sets($keysValues);
		}
		if($this->cacheStoragePassiveMode) {
			foreach($keysValues as $key => $value) {
				$this->cacheStorage->delete($key);
			}
		}
		else {
			$this->cacheStorage->sets($keysValues);
		}
	}

	protected function _delete($key) {
		$this->baseStorage->delete($key);
		$this->cacheStorage->delete($key);
	}

	protected function _mDelete(array $keys) {
		$this->baseStorage->mDelete($keys);
		$this->cacheStorage->mDelete($keys);
	}

	protected function validateGlobalInterface() {
		if(!$this->baseStorage instanceof Storage_Global) {
			throw new Exception('Storage "' . $this->baseStorage->getStorageName() . '" does not implements Storage_Global interface');
		}
		if(!$this->cacheStorage instanceof Storage_Global) {
			throw new Exception('Storage "' . $this->cacheStorage->getStorageName() . '" does not implements Storage_Global interface');
		}
	}

	public function getAll() {
		$this->validateGlobalInterface();
		return $this->baseStorage->getAll();
	}

	public function clear() {
		$this->validateGlobalInterface();
		$this->baseStorage->clear();
		$this->cacheStorage->clear();
	}
}
