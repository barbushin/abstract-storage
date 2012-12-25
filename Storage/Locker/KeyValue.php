<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
*/
class Storage_Locker_KeyValue extends Storage_Locker {

	/** @var Storage_KeyValue */
	protected $storage;

	public function __construct(Storage_KeyValue $storage) {
		$this->storage = $storage;
	}

	protected function getLockInfo($alias) {
		return json_decode($this->storage->get($alias), true);
	}

	public function isLocked($alias) {
		$lockInfo = $this->getLockInfo($alias);
		if($lockInfo) {
			return $lockInfo['expire'] >= time();
		}
		return false;
	}

	/**
	 * @param $alias
	 * @param null $expireSeconds
	 * @return Storage_Locker_Lock
	 * @throws Storage_Locker_AlreadyLocked
	 */
	protected function registerLock($alias, $expireSeconds) {
		if($this->isLocked($alias)) {
			throw new Storage_Locker_AlreadyLocked('Lock "' . $alias . '" is already active');
		}
		$lockId = $this->generateLockId();
		$this->storage->set($alias, json_encode(array(
			'id' => $lockId,
			'expire' => time() + $expireSeconds
		)));
		return new Storage_Locker_Lock($this, $alias, $lockId, $expireSeconds);
	}

	public function unlock(Storage_Locker_Lock $lock) {
		$lockInfo = $this->getLockInfo($lock->getAlias());
		if($lockInfo && $lockInfo['id'] == $lock->getId()) {
			$this->storage->delete($lock->getAlias());
		}
	}
}

