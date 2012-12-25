<?php

abstract class Test_Storage_KeyObject extends Test_Storage_KeyValue {

	/**
	 * @var Storage_KeyObject
	 */
	protected $storage;

	protected function tearDown() {
		$this->storage->clear();
	}

	protected function generateKeysObjects(&$expectedObjects = array()) {
		$objects = array();
		for($i = 5; $i; $i--) {
			$key = mt_rand();
			$object = array(
				'int' => mt_rand(),
				'zero' => 0,
				'string' => md5(mt_rand()),
				'string2' => md5(mt_rand()),
				'stringCommon' => md5(123),
				'boolTrue' => true,
				'boolFalse' => false,
				'null' => null,
				'emptyString' => '',
				'array' => array(
					'sub-array' => array(
						'int' => mt_rand(),
					),
					'string' => md5(mt_rand()),
				),
			);
			$objects[$key] = $object;

			$object['null'] = '';
			$object['boolTrue'] = 1;
			$object['boolFalse'] = 0;
			$expectedObjects[$key] = $object;
		}
		return $objects;
	}

	protected function initTestKeysValue() {
		$this->storeKeysValues = $this->generateKeysObjects($this->expectedKeysValues);
	}

	protected function assertValuesEquals($storeValue, $storedValue, $keysMustEqual = true) {
		$this->assertObjectsEquals($storeValue, $storedValue, $keysMustEqual);
	}

	protected function incrementKey($key, $sum) {
		$this->storage->increment($key, 'zero', $sum);
		$actualObject = $this->storage->get($key);
		$this->assertArrayHasKey('zero', $actualObject);
		return $actualObject['zero'];
	}

	protected function setIncrementKey($key, $value) {
		$this->storage->set($key, array('zero' => $value));
	}

	/**
	 * @expectedException Storage_WrongRequest
	 * @return void
	 */
	public function testSetScalarsThrowsException() {
		$this->storage->set('test', 123);
	}

	public function testSetsGetAllClear() {
		$storeObjects = $this->generateKeysObjects($expectedObjects);
		$this->storage->sets($storeObjects);
		$this->assertObjectsEquals($expectedObjects, $this->storage->getAll());
		$this->storage->clear();
		$this->assertObjectsEquals(array(), $this->storage->getAll());
	}

	public function testFind() {
		$storeObjects = $this->generateKeysObjects($expectedObjects);
		$this->storage->sets($storeObjects);
		$this->assertObjectsEquals($expectedObjects, $this->storage->getAll());
		list($key, $expectedObject) = each($expectedObjects);
		$actualObject = $this->storage->find(new Storage_EqualsCriteria(array('string' => $expectedObject['string'], 'string2' => $expectedObject['string2'])));
		$this->assertObjectsEquals(array($key => $expectedObject), $actualObject);
		$this->assertObjectsEquals($expectedObjects, $this->storage->find(new Storage_EqualsCriteria(array(new Storage_EqualCriteria('stringCommon', $expectedObject['stringCommon'])))));
	}

	public function testDeleteByCriteria() {
		$storeObjects = $this->generateKeysObjects($expectedObjects);
		$this->storage->sets($storeObjects);
		list($key, $expectedObject) = each($expectedObjects);
		$objectCriteria = new Storage_EqualsCriteria(array('string' => $expectedObject['string'], 'string2' => $expectedObject['string2']));
		$this->storage->deleteByCriteria($objectCriteria);
		$this->assertObjectsEquals(array(), $this->storage->find($objectCriteria));
		unset($expectedObjects[$key]);
		$this->assertObjectsEquals($expectedObjects, $this->storage->getAll());
		$this->storage->clear();
		$this->assertObjectsEquals(array(), $this->storage->getAll());
	}

	/**
	 * @expectedException Storage_WrongRequest
	 * @return void
	 */
	public function testWrongPropertiesListThrowsException() {
		$this->storage->setPropertiesNames(array('asd'));
		$this->storage->insert(array('qwe' => 123));
	}
}