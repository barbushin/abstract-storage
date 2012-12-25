<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_KeyObjectEntry extends Storage_KeyValue implements Storage_Global {

	// TODO: check if it should be true
	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	/**
	 * @var Storage_KeyObject
	 */
	protected $storage;
	protected $storeKeyId;

	public function __construct(Storage_KeyObject $storage, $storeKeyId) {
		$this->storage = $storage;
		$this->storeKeyId = $storeKeyId;
	}

	protected function _get($key) {
		$object = $this->storage->get($this->storeKeyId);
		return isset($object[$key]) ? $object[$key] : null;
	}

	protected function _set($key, $value) {
		$object = $this->storage->get($this->storeKeyId);
		$object[$key] = $value;
		$this->storage->set($this->storeKeyId, $object);
	}

	protected function _sets(array $keysValues) {
		$object = $this->storage->get($this->storeKeyId);
		foreach($keysValues as $key => $value) {
			$object[$key] = $value;
		}
		$this->storage->set($this->storeKeyId, $object);
	}

	protected function _delete($key) {
		$object = $this->storage->get($this->storeKeyId);
		if(isset($object[$key])) {
			unset($object[$key]);
			$this->storage->set($this->storeKeyId, $object);
		}
	}

	public function getAll() {
		return $this->storage->get($this->storeKeyId) ? : array();
	}

	public function clear() {
		$this->storage->delete($this->storeKeyId);
	}
}
