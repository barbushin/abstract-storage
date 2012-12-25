<?php

class Test_Storage_KeyValue_Prefixed extends Test_Storage_KeyValue {

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_Prefixed(new Storage_KeyValue_Memory(), 'unittest');
	}
}

