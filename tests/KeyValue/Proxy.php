<?php

class Test_Storage_KeyValue_Proxy extends Test_Storage_KeyValue {

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_Proxy(new Storage_KeyValue_Memory());
	}
}

