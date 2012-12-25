<?php

class Test_Storage_KeyValue_KeyObjectEntry extends Test_Storage_KeyValue {

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_KeyObjectEntry(new Storage_KeyObject_Memory(), mt_rand());
	}
}

