<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Queue_Redis extends Storage_Queue {

	protected $redis;
	protected $key;

	public function __construct(Storage_Redis $redis, $queueKey, $dataSizeLimit = 8000) {
		parent::__construct($dataSizeLimit);
		$this->redis = $redis;
		$this->key = $queueKey;
		$this->setStorageName($redis->getHost() . '/' . $queueKey);
	}

	protected function _push($data) {
		$this->redis->rPush($this->key, $data);
	}

	protected function _pop() {
		return $this->redis->lPop($this->key);
	}
}
