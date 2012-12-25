<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_ByCallback extends Storage_Shard {

	protected $storageInitCallback;

	public function __construct(Storage_Shard_Keys_Provider $keysProvider, $storageInitCallback, $storagesConfigs) {
		if(!is_callable($storageInitCallback)) {
			throw new Exception('Argument $storageInitCallback must be callable');
		}
		$this->storageInitCallback = $storageInitCallback;
		parent::__construct($keysProvider, $storagesConfigs);
	}

	/**
	 * @param $config
	 * @return Storage_Abstract
	 */
	protected function initStorage($config) {
		return call_user_func($this->storageInitCallback, $config);
	}
}
