<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_Keys_Provider_StoredKeys extends Storage_Shard_Keys_Provider {

	/** @var Storage_KeyValue */
	protected $keysStorage;
	/** @var Storage_Locker */
	protected $initLocker;

	public function __construct(Storage_Shard_Keys_Generator $keysGenerator, Storage_KeyValue $keysStorage, $autoInitNewKeys = true, Storage_Locker $initLocker = null) {
		$this->keysStorage = new Storage_KeyValue_Cached($keysStorage, new Storage_KeyValue_Memory(), true);
		$this->autoInitNewKeys = $autoInitNewKeys;
		parent::__construct($keysGenerator, $autoInitNewKeys);
	}

	protected function getKeysStoragesIdsOrNull(array $keys) {
		return $this->keysStorage->gets($keys);
	}

	public function setKeyStorageId($key, $storageId) {
		$this->keysStorage->set($key, $storageId);
	}

	public function initKeyStorageId($key) {
		if($this->isKeyInShard($key)) {
			return $this->getKeyStorageId($key);
		}

		$storageId = $this->keysGenerator->generateId($key);

		if($this->initLocker) {
			$lockAlias = 'shard_init:' . $this->keysStorage->getStorageName() . ':' . $key;
			$lockTimeout = 30;
			try {
				$lock = $this->initLocker->lock($lockAlias, $lockTimeout);
				$this->setKeyStorageId($key, $storageId);
				$lock->unlock();
			}
			catch(Storage_Locker_AlreadyLocked $exception) {
				$this->initLocker->waitUnlock($lockAlias, $lockTimeout);
				$this->keysStorage->getCacheStorage()->clear();
				$storageId = $this->getKeyStorageId($key);
				if($storageId === null) {
					throw new Exception('Locked shard keys initialization failed');
				}
			}
		}
		else {
			$this->setKeyStorageId($key, $storageId);
		}
		return $storageId;
	}

	/**
	 * IMPORTANT: it will return null for unknown keys
	 * @param $keys
	 * @param bool $isRequired
	 * @throws Storage_ShardKeysNotFound
	 * @return array
	 */
	public function getKeysStoragesIds($keys, $isRequired = true) {
		$keysServers = array_filter($this->keysStorage->gets($keys));
		if($isRequired && count($keys) != count($keysServers)) {
			$unknownKeys = array_diff($keys, array_keys($keysServers));
			if($this->autoInitNewKeys) {
				foreach($unknownKeys as $key) { // TODO: optimize by sets
					$keysServers[$this->getKeyStorageId($key, true)] = $key;
				}
			}
			else {
				throw new Storage_ShardKeysNotFound($unknownKeys);
			}
		}
		return $keysServers;
	}

	public function getAllKeys() {
		return array_keys($this->keysStorage->getAll());
	}

	public function clearKeys(array $keys) {
		$this->keysStorage->mDelete($keys);
	}

	public function clearAllKeys() {
		$this->keysStorage->clear();
	}
}
