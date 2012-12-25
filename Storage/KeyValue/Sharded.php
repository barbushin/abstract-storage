<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Sharded extends Storage_KeyValue implements Storage_Global {

	const IS_TRANSACTIONAL = false;
	const HANDLE_STORE_DATA = false;

	/**
	 * @var Storage_Shard
	 */
	protected $shard;

	/**
	 * @var Storage_KeyValue_Memory
	 */
	protected $emptyStorage;

	public function __construct(Storage_Shard $shard) {
		$this->shard = $shard;
		$this->emptyStorage = new Storage_KeyValue_Memory();
	}

	/**
	 * @param $key
	 * @param bool $isRequired
	 * @return Storage_KeyValue
	 */
	protected function getKeyStorage($key, $isRequired = false) {
		return $this->shard->getKeyStorage($key, $isRequired) ? : $this->emptyStorage;
	}

	/**
	 * @param $key
	 * @return Storage_KeyValue
	 */
	protected function _get($key) {
		return $this->getKeyStorage($key)->get($key);
	}

	protected function _set($key, $value) {
		$this->getKeyStorage($key, true)->set($key, $value);
	}

	protected function _delete($key) {
		$this->getKeyStorage($key)->delete($key);
	}

	protected function _mDelete(array $keys) {
		// TODO: optimize by using callStoragesWithAssocKeysValuesArgument if count(storages) > X
		foreach($this->shard->getAllStorages() as $storage) {
			/** @var $storage Storage_KeyValue */
			$storage->mDelete($keys);
		}
	}

	public function increments($keysIncrements) {
		$this->shard->callStoragesWithAssocKeysValuesArgument('increments', $keysIncrements);
	}

	protected function _increment($key, $sum) {
		$this->getKeyStorage($key, true)->increment($key, $sum);
	}

	protected function _sets(array $keysValues) {
		return $this->shard->callStoragesWithAssocKeysValuesArgument('sets', $keysValues);
	}

	protected function _gets(array $keys) {
		$keysValues = $this->shard->callStoragesWithAssocKeysValuesArgument('gets', array_combine($keys, $keys), false);
		if(count($keys) != count($keysValues)) {
			foreach(array_diff($keys, array_keys($keysValues)) as $key) {
				$keysValues[$key] = null;
			}
		}
		return $keysValues;
	}

	public function getAll() {
		return $this->shard->callAllStoragesAndMergeResult('getAll');
	}

	public function clear() {
		$this->shard->callAllStoragesAndMergeResult('clear');
	}
}
