<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Prefixed extends Storage_KeyValue {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	/**
	 * @var Storage_KeyValue
	 */
	protected $storage;
	protected $keysPrefix;

	public function __construct(Storage_KeyValue $storage, $keysPrefix) {
		$this->storage = $storage;
		$this->keysPrefix = $keysPrefix;
	}

	public function getStorageKey($key) {
		return $this->keysPrefix . $key;
	}

	public function getKeysPrefix() {
		return $this->keysPrefix;
	}

	public function getStorage() {
		return $this->storage;
	}

	protected function _gets(array $keys) {
		foreach($keys as &$key) {
			$key = $this->getStorageKey($key);
		}
		$results = array();
		$keyLength = strlen($this->keysPrefix);
		foreach($this->storage->gets($keys) as $key => $value) {
			$results[substr($key, $keyLength)] = $value;
		}
		return $results;
	}

	protected function _sets(array $keysValues) {
		$newKeysValues = array();
		foreach($keysValues as $key => $value) {
			$newKeysValues[$this->getStorageKey($key)] = $value;
		}
		return $this->storage->sets($newKeysValues);
	}

	protected function _increment($key, $sum) {
		return $this->storage->increment($this->getStorageKey($key), $sum);
	}

	protected function _get($key) {
		return $this->storage->get($this->getStorageKey($key));
	}

	protected function _set($key, $value) {
		return $this->storage->set($this->getStorageKey($key), $value);
	}

	protected function _delete($key) {
		return $this->storage->delete($this->getStorageKey($key));
	}

	protected function _mDelete(array $keys) {
		$prefixedKeys = array();
		foreach($keys as $key) {
			$prefixedKeys[] = $this->getStorageKey($key);
		}
		$this->storage->mDelete($prefixedKeys);
	}
}
