<?php

abstract class Test_Storage_KeyValue extends Test_Storage_Abstract {

	/**
	 * @var Storage_KeyValue
	 */
	protected $storage;

	protected $storeKeysValues;
	protected $expectedKeysValues;

	protected static $storeVarsTypes = array(
		'null',
		'num',
		'-num',
		'zero',
		'string',
		'emptyString',
		'boolTrue',
		'boolFalse',
		'array',
		'emptyArray',
	);

	protected function setUp() {
		$this->initTestKeysValue();
	}

	protected function tearDown() {
		parent::tearDown();
		foreach($this->storeKeysValues as $key => $value) {
			$this->storage->delete($key);
		}
	}

	protected function generateKey($type) {
		return '_test_' . $type . mt_rand() . mt_rand();
	}

	protected function initTestKeysValue() {
		$this->storeKeysValues = array();
		$this->expectedKeysValues = array();
		foreach(static::$storeVarsTypes as $type) {
			$key = $this->generateKey($type);
			$customExpectedValue = null;
			$this->storeKeysValues[$key] = $this->generateKeyValue($type, $customExpectedValue);
			$this->expectedKeysValues[$key] = $customExpectedValue !== null ? $customExpectedValue : $this->storeKeysValues[$key];
		}
	}

	protected function generateKeyValue($type, &$customExpectedValue = null) {
		if($type == 'null') {
			$customExpectedValue = '';
			return null;
		}
		elseif($type == 'num') {
			return mt_rand();
		}
		elseif($type == '-num') {
			return mt_rand() * -1;
		}
		elseif($type == 'zero') {
			return 0;
		}
		elseif($type == 'string') {
			return str_repeat('фA1!@#$%^&*)("\'', mt_rand(10, 30)) . md5(mt_rand());
		}
		elseif($type == 'emptyString') {
			return '';
		}
		elseif($type == 'boolTrue') {
			$customExpectedValue = 1;
			return true;
		}
		elseif($type == 'boolFalse') {
			$customExpectedValue = 0;
			return false;
		}
		elseif($type == 'array') {
			return array(mt_rand() => array('asd' => mt_rand()), md5(mt_rand()) => array());
		}
		elseif($type == 'emptyArray') {
			return array();
		}
		else {
			throw new Exception();
		}
	}

	public function testNotFoundGetReturnNull() {
		$this->assertNull($this->storage->get(mt_rand() . mt_rand()));
	}

	/**
	 * @expectedException Storage_WrongRequest
	 * @return void
	 */
	public function testSetWrongValueTypeThrowsException() {
		$this->storage->set(key($this->storeKeysValues), new stdClass());
	}

	/**
	 * @expectedException Storage_WrongRequest
	 * @app provideWrongKeys
	 * @return void
	 */
	public function testSetWrongKeyTypeThrowsException($key) {
		$this->storage->set($key, 123);
	}

	public function provideWrongKeys() {
		return $this->generateProviderCalls(array(
				null,
				'',
				new stdClass(),
				array()));
	}

	public function testGetSetDelete() {
		foreach($this->storeKeysValues as $key => $storeValue) {
			$this->storage->set($key, $storeValue);
			$this->assertValuesEquals($this->expectedKeysValues[$key], $this->storage->get($key));
			$this->storage->delete($key);
			$this->assertNull($this->storage->get($key));
		}
	}

	protected function assertValuesEquals($storeValues, $storedValues, $keysMustEqual = true) {
		$this->assertObjectsEquals($storeValues, $storedValues, $keysMustEqual);
	}

	public function testInsertsGets() {
		$keys = $this->storage->inserts($this->storeKeysValues);
		$this->assertValuesEquals($this->expectedKeysValues, $this->storage->gets($keys), false);
	}

	public function testSetsGets() {
		$this->storage->sets($this->storeKeysValues);
		$this->assertValuesEquals($this->expectedKeysValues, $this->storage->gets(array_keys($this->storeKeysValues)));
		foreach($this->storeKeysValues as $key => $value) {
			$this->storage->delete($key);
		}
		$actualKeysValues = $this->storage->gets(array_keys($this->storeKeysValues));
		foreach($this->storeKeysValues as $key => $value) {
			$this->assertArrayHasKey($key, $actualKeysValues);
			$this->assertNull($actualKeysValues[$key]);
		}
	}

	protected function getRandomIncrement() {
		return mt_rand(0, 1000000000);
	}

	protected function incrementKey($key, $sum) {
		$this->storage->increment($key, $sum);
		return $this->storage->get($key);
	}

	public function testIncrementEmpty() {
		$key = key($this->storeKeysValues);
		$expectedValue = $this->getRandomIncrement();
		$this->assertEquals($expectedValue, $this->incrementKey($key, $expectedValue));
	}

	protected function setIncrementKey($key, $value) {
		$this->storage->set($key, $value);
	}

	public function testPositiveIncrement() {
		$key = key($this->storeKeysValues);
		$originalValue = $this->getRandomIncrement();
		$this->setIncrementKey($key, $originalValue);
		$sumValue = $this->getRandomIncrement();
		$this->assertEquals($originalValue + $sumValue, $this->incrementKey($key, $sumValue));
	}

	public function testNegativeIncrement() {
		$key = key($this->storeKeysValues);
		$originalValue = $this->getRandomIncrement() * -1;
		$this->setIncrementKey($key, $originalValue);
		$sumValue = $this->getRandomIncrement();
		$this->assertEquals($originalValue + $sumValue, $this->incrementKey($key, $sumValue));
	}

	public function testDecrement() {
		$key = key($this->storeKeysValues);
		$originalValue = $this->getRandomIncrement();
		$this->setIncrementKey($key, $originalValue);
		$sumValue = $this->getRandomIncrement() * -1;
		$this->assertEquals($originalValue + $sumValue, $this->incrementKey($key, $sumValue));
	}
}