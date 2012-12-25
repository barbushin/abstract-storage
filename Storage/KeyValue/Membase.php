<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Membase extends Storage_KeyValue {

	/**
	 * @var Memcached
	 */
	protected $membase;

	public function __construct(array $serversList, $persistentSessionName = 'default') {
		$this->membase = $this->initMembaseConnection($serversList, $persistentSessionName);
	}

	protected function initMembaseConnection($serversList, $persistentSessionName) {
		$membase = new Memcached($persistentSessionName);
		$membase->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
		$membase->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

		foreach($serversList as $host) {
			$membase->addServer($host, 11211);
		}
		return $membase;
	}

	protected function _get($key) {
		$result = $this->membase->get($key);
		if($this->membase->getResultCode() == Memcached::RES_NOTFOUND) {
			return null;
		}
		return $result;
	}

	protected function _gets(array $keys) {
		$results = $this->membase->getMulti($keys);
		$this->validateLastResult();
		if(count($results) != count($keys)) {
			foreach($keys as $key) {
				if(!isset($results[$key])) {
					$results[$key] = null;
				}
			}
		}
		return $results;
	}

	protected function _set($key, $value) {
		$this->membase->set($key, $value);
		$this->validateLastResult();
	}

	protected function _delete($key) {
		$this->membase->delete($key);
		$this->validateLastResult();
	}

	protected function _sets(array $keysValues) {
		$this->membase->setMulti($keysValues);
		$this->validateLastResult();
	}

	protected function _increment($key, $sum) {
		if($sum >= 0) {
			$this->membase->increment($key, $sum);
		}
		else {
			$this->membase->decrement($key, abs($sum));
		}
		if($this->membase->getResultCode() == Memcached::RES_NOTFOUND) {
			$this->set($key, $sum);
		}
		$this->validateLastResult();
	}

	/**
	 * @throws Storage_WrongRequest
	 * @return void
	 */
	protected function validateLastResult() {
		$resultCode = $this->membase->getResultCode();
		if($resultCode != Memcached::RES_SUCCESS && $resultCode != Memcached::RES_NOTFOUND) {
			throw new Storage_WrongRequest($this->membase->getResultMessage() . ' (' . $resultCode . ')');
		}
	}
}
