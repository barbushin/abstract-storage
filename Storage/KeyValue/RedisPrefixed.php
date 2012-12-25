<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_RedisPrefixed extends Storage_KeyValue_Prefixed implements Storage_Global {

	/** @var Storage_KeyValue_Redis */
	protected $storage;

	public function __construct(Storage_Redis $redis, $keysPrefix, $timeoutOnSet = null, Storage_Logger $logger = null) {
		parent::__construct(new Storage_KeyValue_Redis($redis, $timeoutOnSet, $logger), $keysPrefix);
	}

	public function getAllKeys($withPrefix = false) {
		$keys = $this->storage->getRedisConnection()->getKeys($this->keysPrefix . '*');
		if(!$withPrefix) {
			$keyPrefixLength = strlen($this->keysPrefix);
			foreach($keys as &$key) {
				$key = substr($key, $keyPrefixLength);
			}
		}
		return $keys;
	}

	public function getAll() {
		return $this->storage->gets($this->getAllKeys(true));
	}

	public function clear() {
		return $this->storage->getRedisConnection()->mDelete($this->getAllKeys(true));
	}
}
