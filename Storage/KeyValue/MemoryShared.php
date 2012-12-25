<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_MemoryShared extends Storage_KeyValue implements Storage_Global {

	protected static $data;

	function __construct($data = array()) {
		$this->_sets($data);
	}

	/**
	 * @param $key
	 * @return
	 */
	protected function _get($key) {
		return isset(self::$data[$key]) ? self::$data[$key] : null;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return void
	 */
	protected function _set($key, $value) {
		self::$data[$key] = $value;
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _delete($key) {
		if(isset(self::$data[$key])) {
			unset(self::$data[$key]);
		}
	}

	public function clear() {
		self::$data = array();
	}

	public function getAll() {
		return self::$data;
	}
}
