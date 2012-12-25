<?php
 
/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 * @desc Redis PHP extension interface, see https://github.com/nicolasff/phpredis
 */
class Storage_Redis extends Storage_Abstract {

	// TODO: add throwing Storage_Redis_RequestFailed for actual methods

	/** @var Redis */
	protected $redis;
	/** @var Redis */
	protected $trueRedis;

	/** @var Redis|null */
	protected $redisTransactionInstance;
	protected $redisTransactionLevel = 0;

	protected $host;

	protected $isMultiActive;

	public function __construct($host, $port = 6379, $timeout = 10) {
		$this->host = $host;
		$this->redis = $this->initRedisConnection($host, $port, $timeout);
		$this->trueRedis = new Storage_RedisTrueResultCaller($this->redis);
		$this->setStorageName($host);
	}

	public function begin() {
		if(!$this->redisTransactionInstance) {
			$this->redisTransactionInstance = $this->redis;
		}
		if(!$this->redisTransactionLevel) {
			$this->redis = $this->redisTransactionInstance->multi();
		}
		$this->redisTransactionLevel++;
		return $this;
	}

	public function commit() {
		if(!$this->redisTransactionLevel) {
			throw new Exception('There is no active Redis transaction');
		}
		$this->redisTransactionLevel--;
		$result = null;
		if(!$this->redisTransactionLevel) {
			$result = $this->redis->exec();
			$this->redis = $this->redisTransactionInstance;
		}
		return $result;
	}

	public function rollback() {
		if(!$this->redisTransactionLevel) {
			throw new Exception('There is no active Redis transaction');
		}
		$this->redis->discard();
		$this->redisTransactionLevel = 0;
		$this->redis = $this->redisTransactionInstance;
	}

	protected static function replaceFalseToNull($data) {
		return $data === false ? null : $data;
	}

	public function getHost() {
		return $this->host;
	}

	public function setex($key, $timeout, $value) {
		return $this->trueRedis->setex($key, $timeout, $value);
	}

	protected function initRedisConnection($host, $port, $timeout) {
		$redis = new Redis();
		try {
			// TODO: add auth options

			$hostOrIp = class_exists('HostsWrapper', true) ? HostsWrapper::forceIp($this->host) : $this->host;
			@$redis->connect($hostOrIp, $port, $timeout);
		}
		catch(Exception $exception) {
			throw new Storage_Redis_ConnectionFailed('Connection to "' . $host . ':' . $port . '" failed');
		}
		return $redis;
	}

	public function get($key) {
		return static::replaceFalseToNull($this->redis->get($key));
	}

	public function mGet($keys) {
		$result = array();
		$data = $this->redis->mGet($keys);
		foreach(array_values($keys) as $i => $key) {
			$result[$key] = isset($data[$i]) ? static::replaceFalseToNull($data[$i]) : null;
		}
		return $result;
	}

	public function set($key, $value) {
		$this->trueRedis->set($key, $value);
	}

	public function mSet($keysValues) {
		$this->trueRedis->mSet($keysValues);
	}

	public function delete($key) {
		$this->redis->delete($key);
	}

	public function mDelete(array $keys) {
		$this->begin();
		foreach($keys as $key) {
			$this->delete($key);
		}
		$this->commit();
	}

	public function hMDelete($hKey, array $keys) {
		$this->begin();
		foreach($keys as $key) {
			$this->hDel($hKey, $key);
		}
		$this->commit();
	}

	public function mHmSet(array $hKeys, $key, $value) {
		$this->begin();
		foreach($hKeys as $hKey) {
			$this->hSet($hKey, $key, $value);
		}
		$this->commit();
	}

	public function incr($key, $sum) {
		$this->trueRedis->incr($key, $sum);
	}

	public function mIncr($keysSums) {
		$this->begin();
		foreach($keysSums as $key => $sum) {
			$this->incr($key, $sum);
		}
		$this->commit();
	}

	public function setnx($key, $value) {
		return $this->redis->setnx($key, $value);
	}

	public function getKeys($searchPattern) {
		return $this->redis->getKeys($searchPattern);
	}

	public function setTimeout($key, $timeout) {
		$this->trueRedis->setTimeout($key, $timeout);
	}

	public function mSetTimeout(array $keys, $timeout) {
		$this->begin();
		foreach($keys as $key) {
			$this->setTimeout($key, $timeout);
		}
		$this->commit();
	}

	public function getIdleTime($key) {
		return $this->redis->object('idletime', $key);
	}

	public function rPush($key, $value) {
		return $this->trueRedis->rPush($key, $value);
	}

	public function lPush($key, $value) {
		return $this->trueRedis->lPush($key, $value);
	}

	public function lPop($key) {
		return static::replaceFalseToNull($this->redis->lPop($key));
	}

	public function rPop($key) {
		return static::replaceFalseToNull($this->redis->rPop($key));
	}

	public function __call($method, $args = array()) {
		return call_user_func_array(array($this->redis, $method), $args);
	}

	public function __destruct() {
		if($this->redisTransactionLevel) {
			$this->rollback();
		}
	}
}

class Storage_RedisTrueResultCaller {

	protected $redis;

	public function __construct($redis) {
		$this->redis = $redis;
	}

	public function __call($method, $args = array()) {
		$result = call_user_func_array(array($this->redis, $method), $args);
		if($result === false) {
			throw new Storage_Redis_RequestFailed('Redis method "' . $method . '" returns false');
		}
		return $result;
	}
}

class Storage_Redis_ConnectionFailed extends Storage_ConnectionFailed {

}

class Storage_Redis_RequestFailed extends Storage_Exception {

}

