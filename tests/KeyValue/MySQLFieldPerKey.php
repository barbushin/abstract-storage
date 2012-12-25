<?php

class Test_Storage_KeyValue_MySQLFieldPerKey extends Test_Storage_KeyValue {

	protected static $storeVarsTypes = array(
		'num',
		'string',
		'boolTrue',
	);

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_MySQLFieldPerKey(new Storage_MySQL('localhost', 'root', '', 'test'), 'key_value_field_per_key', mt_rand(), static::$storeVarsTypes, 'id');
	}

	public function testNotFoundGetReturnNull() {
		$this->markTestSkipped('Storage works only with fixed list of keys');
	}

	public function testInsertsGets() {
		$this->markTestSkipped('Storage does not support insert() operation');
	}

	protected function generateKey($type) {
		return $type;
	}
}

