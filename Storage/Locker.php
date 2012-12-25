<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Locker {

	/** @var Storage_Locker_Lock[] */
	protected $selfLocks = array();

	abstract public function isLocked($alias);

	/**
	 * @param $alias
	 * @param null $expireSeconds
	 * @return Storage_Locker_Lock
	 * @throws Storage_Locker_AlreadyLocked
	 */
	abstract protected function registerLock($alias, $expireSeconds);

	/**
	 * @abstract
	 * @param Storage_Locker_Lock $lock
	 */
	abstract public function unlock(Storage_Locker_Lock $lock);

	/**
	 * @param $alias
	 * @param $expireSeconds
	 * @return Storage_Locker_Lock
	 * @throws Storage_Locker_AlreadyLocked
	 */
	final public function lock($alias, $expireSeconds) {
		$lock = $this->registerLock($alias, $expireSeconds);
		$this->selfLocks[$lock->getId()] = $lock;
		return $lock;
	}

	public function waitUnlock($alias, $waitTimeout = 30, $checkDelay = 0.1) {
		$startTime = microtime(true);
		$wasLocked = false;
		while(microtime(true) - $startTime < $waitTimeout) {
			if(!$this->isLocked($alias)) {
				return $wasLocked;
			}
			else {
				$wasLocked = true;
				usleep($checkDelay * 1000000);
			}
		}
		throw new Storage_Locker_UnlockWaitingExpired('Waiting unlock "' . $alias . '" is expired');
	}

	public function waitAndLock($alias, $expireSeconds, $waitTimeout = 30, $checkDelay = 0.1) {
		$startTime = microtime(true);
		while(microtime(true) - $startTime < $waitTimeout) {
			try {
				return $this->lock($alias, $expireSeconds);
			}
			catch(Storage_Locker_AlreadyLocked $exception) {
				usleep($checkDelay * 1000000);
			}
		}
		throw new Storage_Locker_UnlockWaitingExpired('Waiting unlock "' . $alias . '" is expired');
	}

	protected function generateLockId() {
		return mt_rand() . mt_rand();
	}

	public function __destruct() {
		foreach($this->selfLocks as $lock) {
			if(!$lock->isUnlocked()) {
				$this->unlock($lock);
			}
		}
	}
}

class Storage_Locker_Lock {

	/** @var Storage_Locker */
	protected $locker;
	protected $alias;
	protected $id;
	protected $timeout;
	protected $startTime;
	protected $isUnlocked;

	public function __construct(Storage_Locker $locker, $alias, $id, $timeout) {
		$this->locker = $locker;
		$this->alias = $alias;
		$this->id = $id;
		$this->timeout = $timeout;
		$this->startTime = time();
	}

	public function isExpired() {
		return time() - $this->startTime >= $this->timeout;
	}

	public function getAlias() {
		return $this->alias;
	}

	public function getId() {
		return $this->id;
	}

	public function getTimeout() {
		return $this->timeout;
	}

	public function isUnlocked() {
		return $this->isUnlocked;
	}

	public function unlock() {
		$this->isUnlocked = true;
		$this->locker->unlock($this);
	}
}

class Storage_Locker_AlreadyLocked extends Exception {

}

class Storage_Locker_UnlockWaitingExpired extends Exception {

}



