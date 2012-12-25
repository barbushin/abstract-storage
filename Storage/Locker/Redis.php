<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Locker_Redis extends Storage_Locker {

	/** @var Storage_Redis */
	protected $redis;
	protected $keysPrefix;

	public function __construct(Storage_Redis $redis, $keysPrefix = 'lock:') {
		$this->redis = $redis;
		$this->keysPrefix = $keysPrefix;
	}

	protected function getKey($alias) {
		return $this->keysPrefix . $alias;
	}

	public function isLocked($alias) {
		return $this->redis->get($this->getKey($alias)) !== null;
	}

	/**
	 * @param $alias
	 * @param null $expireSeconds
	 * @return Storage_Locker_Lock
	 * @throws Storage_Locker_AlreadyLocked
	 */
	protected function registerLock($alias, $expireSeconds) {
		$lockId = $this->generateLockId();
		$result = $this->redis->setnx($this->getKey($alias), $lockId);
		if($result === false) {
			throw new Storage_Locker_AlreadyLocked('Lock "' . $alias . '" is already active');
		}
		$this->redis->setTimeout($this->getKey($alias), $expireSeconds);
		return new Storage_Locker_Lock($this, $alias, $lockId, $expireSeconds);
	}

	public function unlock(Storage_Locker_Lock $lock) {
		if($this->redis->get($this->getKey($lock->getAlias())) == $lock->getId()) {
			// TODO: there is 0.0000001% chance that it will unlock another transaction, try to find some better way
			$this->redis->delete($this->getKey($lock->getAlias()));
		}
	}
}

