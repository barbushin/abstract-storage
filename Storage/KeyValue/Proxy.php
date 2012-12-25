<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_KeyValue_Proxy extends Storage_KeyValue implements Storage_Global {

	const IS_TRANSACTIONAL = false;
	const HANDLE_STORE_DATA = false;

	/**
	 * @var Storage_KeyValue|Storage_Global
	 */
	protected $storage;

	public function __construct(Storage_KeyValue $storage) {
		$this->storage = $storage;
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return $this->storage->get($key);
	}

	protected function _set($key, $value) {
		$this->storage->set($key, $value);
	}

	protected function _delete($key) {
		$this->storage->delete($key);
	}

	public function increments($keysIncrements) {
		$this->storage->increments($keysIncrements);
	}

	protected function _increment($key, $sum) {
		$this->storage->increment($key, $sum);
	}

	protected function _sets(array $keysValues) {
		$this->storage->sets($keysValues);
	}

	protected function _gets(array $keys) {
		return $this->storage->gets($keys);
	}

	protected function _inserts($values) {
		return $this->storage->inserts($values);
	}

	protected function _mDelete(array $keys) {
		$this->storage->mDelete($keys);
	}

	protected function validateGlobalInterface() {
		if(!$this->storage instanceof Storage_Global) {
			throw new Exception('Storage "' . $this->storage->getStorageName() . '" does not implements Storage_Global interface');
		}
	}

	public function getAll() {
		$this->validateGlobalInterface();
		return $this->storage->getAll();
	}

	public function clear() {
		$this->validateGlobalInterface();
		return $this->storage->clear();
	}
}
