<?php

class Test_Storage_KeyObject_MemoryShared extends Test_Storage_KeyObject {

	/**
	 * @return Storage_KeyObject
	 */
	protected function initStorage(array $propertiesNames = array()) {
		return new Storage_KeyObject_MemoryShared(array(), $propertiesNames);
	}

	protected function setUp() {
		$this->markTestSkipped('FIX IT');
		parent::setUp();
	}
}

