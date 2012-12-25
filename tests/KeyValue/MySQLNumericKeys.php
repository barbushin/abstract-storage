<?php

class Test_Storage_KeyValue_MySQLNumericKeys extends Test_Storage_KeyValue {

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_MySQLNumericKeys(new Storage_MySQL('localhost', 'root', '', 'test'), 'key_value_numeric');
	}

	protected function generateKey($type) {
		return mt_rand();
	}
}

