<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Shard_Keys_Provider {

	/** @var Storage_Shard_Keys_Generator */
	protected $keysGenerator;
	protected $autoInitNewKeys;

	abstract public function initKeyStorageId($key);

	abstract protected function getKeysStoragesIdsOrNull(array $keys);

	abstract public function clearKeys(array $keys);

	public function __construct(Storage_Shard_Keys_Generator $keysGenerator, $autoInitNewKeys = true) {
		$this->keysGenerator = $keysGenerator;
		$this->autoInitNewKeys = $autoInitNewKeys;
	}

	public function getKeyStorageIdOrNull($key) {
		$keysStorages = $this->getKeysStoragesIdsOrNull(array($key));
		return reset($keysStorages);
	}

	public function getAllStoragesIds() {
		return $this->keysGenerator->getAllIds();
	}

	public function isKeyInShard($key) {
		return $this->getKeyStorageIdOrNull($key) !== null;
	}

	/**
	 * @param $key
	 * @param bool $isRequired
	 * @throws Storage_ShardKeysNotFound
	 * @return int
	 */
	public function getKeyStorageId($key, $isRequired = true) {
		$storageId = $this->getKeyStorageIdOrNull($key);
		if($storageId === null && $isRequired) {
			if($this->autoInitNewKeys) {
				$storageId = $this->initKeyStorageId($key);
			}
			else {
				throw new Storage_ShardKeysNotFound(array($key));
			}
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
		$keysServers = $this->getKeysStoragesIdsOrNull($keys);
		if($isRequired) {
			$unknownKeys = array();
			foreach($keysServers as $key => $storageId) {
				if($storageId === null) {
					$unknownKeys[] = $key;
				}
			}
			if($unknownKeys) {
				if($this->autoInitNewKeys) {
					foreach($unknownKeys as $key) { // TODO: optimize by sets
						$keysServers[$key] = $this->getKeyStorageId($key, true);
					}
				}
				else {
					throw new Storage_ShardKeysNotFound($unknownKeys);
				}
			}
		}
		return $keysServers;
	}

	/**
	 * IMPORTANT: it will strip unknown keys
	 * @param $keys
	 * @param bool $isRequired
	 * @return array
	 */
	public function groupKeysByServers($keys, $isRequired = true) {
		$serversKeys = array();
		foreach($this->getKeysStoragesIds($keys, $isRequired) as $key => $storageId) {
			$serversKeys[$storageId][] = $key;
		}
		return $serversKeys;
	}
}

class Storage_ShardServersNotFound extends Exception {

	protected $serversIds;

	public function __construct(array $unknownServersIds) {
		$this->serversIds = $unknownServersIds;
		parent::__construct('Unknown shard servers ids: ' . implode(', ', $unknownServersIds));
	}

	public function getServersIds() {
		return $this->serversIds;
	}
}

class Storage_ShardKeysNotFound extends Exception {

	protected $keys;

	public function __construct(array $unknownKeys) {
		$this->keys = $unknownKeys;
		parent::__construct('Keys not found in shard: ' . implode(', ', $unknownKeys));
	}

	public function getKeys() {
		return $this->keys;
	}
}
