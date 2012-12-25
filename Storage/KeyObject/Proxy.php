<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_KeyObject_Proxy extends Storage_KeyObject {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	/**
	 * @var Storage_KeyObject
	 */
	protected $storage;

	public function __construct(Storage_KeyObject $storage) {
		$this->storage = $storage;
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		return $this->storage->find($criteria);
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		return $this->storage->deleteByCriteria($criteria);
	}

	protected function _inserts($values) {
		return $this->storage->inserts($values);
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return $this->storage->get($key);
	}

	protected function _gets(array $keys) {
		return $this->storage->gets($keys);
	}

	protected function _set($key, $value) {
		return $this->storage->set($key, $value);
	}

	protected function _sets(array $keysValues) {
		return $this->storage->sets($keysValues);
	}

	protected function _delete($key) {
		return $this->storage->delete($key);
	}

	protected function _increment($key, $property, $sum = 1) {
		return $this->storage->increment($key, $property, $sum);
	}
}

