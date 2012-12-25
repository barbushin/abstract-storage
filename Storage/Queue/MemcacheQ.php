<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Queue_MemcacheQ extends Storage_Queue {

	protected $host;
	protected $port;
	protected $key;

	public function __construct($queueKey, $host = 'localhost', $port = 22201, $dataSizeLimit = 8000) {
		parent::__construct($dataSizeLimit);
		$this->host = $host;
		$this->port = $port;
		$this->key = $queueKey;
		$this->setStorageName($host . '/' . $queueKey);
	}

	protected function getConnection($retry = 2) {
		static $connection;
		if(!$connection || !is_resource($connection)) {
			$connection = @memcache_pconnect($this->host, $this->port);
		}
		if(!$connection) {
			if($retry) {
				usleep(100000);
				return $this->getConnection($retry - 1);
			}
			throw new Storage_ConnectionFailed('Connection to MemcacheQ server "' . $this->host . ':' . $this->port . '" failed');
		}
		return $connection;
	}

	protected function _push($data) {
		$success = @memcache_set($this->getConnection(), $this->key, $data, MEMCACHE_COMPRESSED, 0);
		if($success === false) {
			throw new Storage_Exception('memcache_set failed');
		}
	}

	protected function _pop() {
		$result = @memcache_get($this->getConnection(), $this->key);
		if($result === false) {
			$result = null;
		}
		return $result;
	}
}
