<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_Partition extends Storage_Shard_ByCallback {

	protected $storageNamePrefix;
	protected $storageInitCallback;

	public function __construct(Storage_Shard_Keys_Provider $keysProvider, $storageNamePrefix, $storageInitByNameCallback) {
		$this->storageNamePrefix = $storageNamePrefix;
		$storagesConfigs = array();
		foreach($keysProvider->getAllStoragesIds() as $storageId) {
			$storagesConfigs[$storageId] = $this->getStorageName($storageId);
		}
		parent::__construct($keysProvider, $storageInitByNameCallback, $storagesConfigs);
	}

	public function getStorageName($storageId) {
		return $this->storageNamePrefix . $storageId;
	}

	/**
	 * @param $name
	 * @internal param $config
	 * @return Storage_Abstract
	 */
	protected function initStorage($name) {
		return call_user_func($this->storageInitCallback, $name);
	}
}
