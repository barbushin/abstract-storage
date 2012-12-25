<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_RedisHash extends Storage_KeyValue implements Storage_Global {

	/**
	 * @var Storage_Redis
	 */
	protected $redis;
	protected $hKey;
	protected $hKeyTimeoutOnSet;

	public function __construct(Storage_Redis $redis, $hKey, $hKeyTimeoutOnSet = null, Storage_Logger $logger = null) {
		$this->redis = $redis;
		$this->hKey = $hKey;
		$this->hKeyTimeoutOnSet = $hKeyTimeoutOnSet;
		if($logger) {
			$this->setLogger($logger);
		}
		$this->setStorageName($redis->getHost() . '/' . $hKey);
	}

	public function getRedisConnection() {
		return $this->redis;
	}

	protected function onBeforeSets() {
		if($this->hKeyTimeoutOnSet) {
			$this->redis->begin();
		}
	}

	protected function onAfterSets() {
		if($this->hKeyTimeoutOnSet) {
			$this->redis->setTimeout($this->hKey, $this->hKeyTimeoutOnSet);
			$this->redis->commit();
		}
	}

	protected function _get($key) {
		return $this->redis->hGet($this->hKey, $key);
	}

	protected function _gets(array $keys) {
		return $this->redis->hmGet($this->hKey, $keys);
	}

	protected function _set($key, $value) {
		$this->onBeforeSets();
		$this->redis->hSet($this->hKey, $key, $value);
		$this->onAfterSets();
	}

	protected function _sets(array $keysValues) {
		$this->onBeforeSets();
		$this->redis->hMset($this->hKey, $keysValues);
		$this->onAfterSets();
	}

	protected function _delete($key) {
		$this->redis->hDel($this->hKey, $key);
	}

	protected function _increment($key, $sum) {
		$this->onBeforeSets();
		$this->redis->hIncrBy($this->hKey, $key, $sum);
		$this->onAfterSets();
	}

	protected function _mDelete(array $keys) {
		$this->redis->hMDelete($this->hKey, $keys);
	}

	public function getAll() {
		return $this->redis->hGetAll($this->hKey);
	}

	public function clear() {
		$this->redis->delete($this->hKey);
	}
}

