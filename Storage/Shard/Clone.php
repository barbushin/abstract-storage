<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_Clone extends Storage_Shard {

	protected $storageInitCallback;

	public function __construct(Storage_Shard $shard, $storageInitCallback) {
		if(!is_callable($storageInitCallback)) {
			throw new Exception('Argument $storageInitCallback must be callable');
		}
		$this->storageInitCallback = $storageInitCallback;
		parent::__construct($shard->getKeysProvider(), $shard->getAllStorages());
	}

	/**
	 * @param $storage
	 * @return Storage_Abstract
	 */
	protected function initStorage($storage) {
		return call_user_func($this->storageInitCallback, $storage);
	}
}
