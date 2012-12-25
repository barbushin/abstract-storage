<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_Keys_Provider_StaticConfig extends Storage_Shard_Keys_Provider {

	public function __construct(Storage_Shard_Keys_Generator $keysGenerator, $autoInitNewKeys = true) {
		if($keysGenerator::IS_RANDOM) {
			throw new Exception('You can\'t use random keys generator there');
		}
		parent::__construct($keysGenerator, $autoInitNewKeys);
	}

	protected function getKeysStoragesIdsOrNull(array $keys) {
		$keysStoragesIds = array();
		foreach($keys as $key) {
			$keysStoragesIds[$key] = $this->keysGenerator->generateId($key);
		}
		return $keysStoragesIds;
	}

	public function initKeyStorageId($key) {
		return $this->keysGenerator->generateId($key);
	}

	public function clearKeys(array $keys) {
	}
}
