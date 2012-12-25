<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Memcache extends Storage_KeyValue {

	/**
	 * @var Memcache
	 */
	protected $memcache;

	public function __construct($host, $port = 11211, Storage_Logger $logger = null) {
		$this->memcache = new Memcache();
		$isConnected = @$this->memcache->connect($host, $port);
		if(!$isConnected) {
			throw new Storage_Memcache_ConnectionFailed('Connection to "' . $host . ':' . $port . '" failed');
		}
		if($logger) {
			$this->setLogger($logger);
		}
		$this->setStorageName($host);
	}

	protected function _get($key) {
		$data = $this->memcache->get($key);
		return $data !== false ? $data : null;
	}

	protected function _set($key, $value) {
		$this->memcache->set($key, $value);
	}

	protected function _delete($key) {
		$this->memcache->delete($key);
	}

	protected function _increment($key, $sum) {
		$this->memcache->increment($key, $sum);
	}
}

class Storage_Memcache_ConnectionFailed extends Storage_ConnectionFailed {

}