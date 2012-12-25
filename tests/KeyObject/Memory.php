<?php

class Test_Storage_KeyObject_Memory extends Test_Storage_KeyObject {

	/**
	 * @return Storage_KeyObject
	 */
	protected function initStorage(array $propertiesNames = array()) {
		return new Storage_KeyObject_Memory(array(), $propertiesNames);
	}
}

