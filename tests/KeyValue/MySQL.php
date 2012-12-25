<?php

class Test_Storage_KeyValue_MySQL extends Test_Storage_KeyValue {

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_MySQL(new Storage_MySQL('localhost', 'root', '', 'test'), 'key_value');
	}
}

