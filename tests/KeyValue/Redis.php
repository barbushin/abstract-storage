<?php

class Test_Storage_KeyValue_Redis extends Test_Storage_KeyValue {

	protected function initStorage() {
		if(class_exists('Redis')) {
			return new Storage_KeyValue_Redis('web01.hero.exteer.ru');
		}
		else {
			return new Storage_KeyValue_Memory();
		}
	}

	protected function isRedisAvailable() {
	}

	protected function setUp() {
		if(!class_exists('Redis')) {
			$this->markTestSkipped('Redis extension is not installed');
		}
		parent::setUp();
	}
}
