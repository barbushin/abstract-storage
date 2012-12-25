<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Shard {

	/** @var Storage_Shard_Keys_Provider */
	protected $keysProvider;

	protected $storagesConfigs;

	/** @var Storage_Abstract[] */
	protected $initializedStorages = array();

	/**
	 * @abstract
	 * @param $config
	 * @return Storage_Abstract
	 */
	abstract protected function initStorage($config);

	public function __construct(Storage_Shard_Keys_Provider $keysProvider, $storagesConfigs) {
		$this->keysProvider = $keysProvider;
		$this->storagesConfigs = $storagesConfigs;
	}

	protected function getAllStoragesIds() {
		return array_keys($this->storagesConfigs);
	}

	public function getKeysProvider() {
		return $this->keysProvider;
	}

	/**
	 * @return Storage_Abstract[]
	 */
	public function getAllStorages() {
		$storages = array();
		foreach($this->getAllStoragesIds() as $storageId) {
			$storages[$storageId] = $this->getStorageById($storageId);
		}
		return $storages;
	}

	public function callStoragesWithAssocKeysValuesArgument($method, $keysValuesArgument = array(), $isRequired = true) {
		$result = array();
		foreach($this->keysProvider->groupKeysByServers(array_keys($keysValuesArgument), $isRequired) as $storageId => $keys) {
			$storageKeysValuesArgument = array();
			foreach($keys as $key) {
				if(!isset($keysValuesArgument[$key])) {
					throw new Storage_ShardKeysNotFound(array($key));
				}
				$storageKeysValuesArgument[$key] = $keysValuesArgument[$key];
			}
			$callResult = call_user_func(array($this->getStorageById($storageId), $method), $storageKeysValuesArgument);
			if($callResult) {
				$result = array_merge($result, $result);
			}
		}
		return $result;
	}

	public function callAllStoragesAndMergeResult($method, $arguments = array()) {
		$result = array();
		foreach($this->getAllStorages() as $storage) {
			$storageResult = call_user_func(array($storage, $method), $arguments);
			if(is_array($storageResult)) {
				foreach($storageResult as $key => $value) {
					if(isset($result[$key])) {
						$result[] = $value;
					}
					else {
						$result[$key] = $value;
					}
				}
			}
			else {
				$result[] = $storageResult;
			}
		}
		return $result;
	}

	/**
	 * @param $key
	 * @param bool $isRequired
	 * @return Storage_Abstract
	 */
	public function getKeyStorage($key, $isRequired = true) {
		$storageId = $this->keysProvider->getKeyStorageId($key, $isRequired);
		if($storageId !== null) {
			return $this->getStorageById($storageId);
		}
	}

	/**
	 * @param $storageId
	 * @throws Storage_ShardServersNotFound
	 * @return Storage_Abstract
	 */
	public function getStorageById($storageId) {
		if(!isset($this->storagesConfigs[$storageId])) {
			throw new Storage_ShardServersNotFound(array($storageId));
		}
		if(!isset($this->initializedStorages[$storageId])) {
			$this->initializedStorages[$storageId] = $this->initStorage($this->storagesConfigs[$storageId]);
		}
		return $this->initializedStorages[$storageId];
	}

	public function __call($method, $arguments = array()) {
		return $this->callAllStoragesAndMergeResult($method, $arguments);
	}
}




