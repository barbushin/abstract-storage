<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Redis extends Storage_KeyValue {

	/**
	 * @var Storage_Redis
	 */
	protected $redis;
	protected $timeoutOnSet;

	public function __construct(Storage_Redis $redis, $timeoutOnSet = null, Storage_Logger $logger = null) {
		$this->redis = $redis;
		$this->timeoutOnSet = $timeoutOnSet;
		$this->setStorageName($redis->getHost());
		if($logger) {
			$this->setLogger($logger);
		}
	}

	public function getRedisConnection() {
		return $this->redis;
	}

	protected function _get($key) {
		return $this->redis->get($key);
	}

	protected function _gets(array $keys) {
		return $this->redis->mGet($keys);
	}

	protected function _set($key, $value) {
		if($this->timeoutOnSet) {
			$this->redis->setex($key, $this->timeoutOnSet, $value);
		}
		else {
			$this->redis->set($key, $value);
		}
	}

	protected function _sets(array $keysValues) {
		$this->onBeforeSets();
		$this->redis->mSet($keysValues);
		$this->onAfterSets(array_keys($keysValues));
	}

	protected function onBeforeSets() {
		if($this->timeoutOnSet) {
			$this->redis->begin();
		}
	}

	protected function onAfterSets(array $keys) {
		if($this->timeoutOnSet) {
			if(count($keys) > 1) {
				$this->redis->mSetTimeout($keys, $this->timeoutOnSet);
			}
			else {
				$this->redis->setTimeout(reset($keys), $this->timeoutOnSet);
			}
			$this->redis->commit();
		}
	}

	protected function _delete($key) {
		$this->redis->delete($key);
	}

	protected function _increment($key, $sum) {
		$this->onBeforeSets();
		$this->redis->incr($key, $sum);
		$this->onAfterSets(array($key));
	}

	public function increments($keysIncrements) {
		$this->redis->begin();
		$this->onBeforeSets();
		foreach($keysIncrements as $key => $sum) {
			$this->redis->incr($key, $sum);
		}
		$this->onAfterSets(array_keys($keysIncrements));
		$this->redis->commit();
	}

	protected function _mDelete(array $keys) {
		$this->redis->mDelete($keys);
	}
}
