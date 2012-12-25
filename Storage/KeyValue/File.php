<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_File extends Storage_KeyValue implements Storage_Global {

	protected static $sharedData;

	protected $data;
	protected $filepath;
	protected $flushPerWrite;

	public function __construct($filepath, $flushPerWrite = false) {
		$this->flushPerWrite = $flushPerWrite;
		if(!is_file($filepath)) { // required for correct work realpath()
			file_put_contents($filepath, serialize(array()));
		}
		$this->filepath = realpath($filepath); // required for file_put_contents correct work in __destruct()
		$this->data =& self::$sharedData[$this->filepath];
		if($this->data === null) {
			$this->initData();
		}
	}

	protected function initData() {
		$this->data = @unserialize(@file_get_contents($this->filepath)) ?: array();
	}

	protected function saveData() {
		file_put_contents($this->filepath, serialize($this->data));
	}

	protected function flushData() {
		$this->saveData();
		$this->initData();
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	protected function _inserts($values) {
		$key = parent::_inserts($values);
		if($this->flushPerWrite) {
			$this->flushData();
		}
		return $key;
	}

	protected function _sets(array $keysValues) {
		parent::_sets($keysValues);
		if($this->flushPerWrite) {
			$this->flushData();
		}
	}

	protected function _set($key, $value) {
		$this->data[$key] = $value;
		if($this->flushPerWrite) {
			$this->flushData();
		}
	}

	protected function _delete($key) {
		if(isset($this->data[$key])) {
			unset($this->data[$key]);
		}
		if($this->flushPerWrite) {
			$this->flushData();
		}
	}

	public function clear() {
		$this->data = array();
		if($this->flushPerWrite) {
			$this->flushData();
		}
	}

	public function getAll() {
		return $this->data;
	}

	public function __destruct() {
		$this->saveData();
	}
}
