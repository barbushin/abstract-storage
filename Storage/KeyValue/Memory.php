<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Memory extends Storage_KeyValue implements Storage_Global {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;

	protected $data;
	protected $flushDataCallback;
	protected $isChanged;

	function __construct($data = array(), $flushDataCallback = null) {
		$this->data = $data;
		if($flushDataCallback) {
			$this->setFlushDataCallback($flushDataCallback);
		}
	}

	public function setFlushDataCallback($callback) {
		if(!is_callable($callback)) {
			throw new Exception('Argument $callback is not callable');
		}
		if($this->flushDataCallback) {
			throw new Exception('Flush callback already defined');
		}
		$this->flushDataCallback = $callback;
	}

	public function isChanged() {
		return $this->isChanged;
	}

	public function flushIsChanged() {
		$this->isChanged = false;
	}

	/**
	 * @param $key
	 * @return
	 */
	protected function _get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return void
	 */
	protected function _set($key, $value) {
		$this->data[$key] = $value;
		$this->isChanged = true;
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _delete($key) {
		if(isset($this->data[$key])) {
			unset($this->data[$key]);
			$this->isChanged = true;
		}
	}

	public function clear() {
		$this->data = array();
		$this->isChanged = true;
	}

	public function getAll() {
		return $this->data;
	}

	public function __destruct() {
		if($this->isChanged) {
			$this->flushIsChanged();
			if($this->flushDataCallback) {
				call_user_func($this->flushDataCallback, $this);
			}
		}
	}
}
