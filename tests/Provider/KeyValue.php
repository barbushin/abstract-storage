<?php

abstract class Test_Storage_Provider_KeyValue extends Test_Storage_Provider_Abstract {

	const PROVIDED_STORAGE_CLASS = 'Storage_KeyValue';

	/**
	 * @var Storage_KeyValueProvider
	 */
	protected $provider;

	/**
	 * @abstract
	 * @param $collectionName
	 * @return Storage_KeyValue
	 */
	protected function getProviderCollection($provider, $collectionName) {
		return $provider->getKeyValueCollection($collectionName);
	}

	protected function getCollection($collectionName) {
		return $this->getProviderCollection($this->provider, $collectionName);
	}

	protected function generateStoreValue() {
		return mt_rand();
	}

	/**
	 * @param $collectionName
	 * @app provideCollectionsNames
	 * @return void
	 */
	public function testSameSetGetDelete($collectionName) {
		$this->markTestSkipped('MUST BE FIXED');
		return;
		$key = 'asd';

		$collection11 = $this->getCollection($collectionName);
		$this->assertNull($collection11->get($key));
		$expectedValue = $this->generateStoreValue();
		$collection11->set($key, $expectedValue);

		var_dump(count(Flyweighter::$data));

		$collection12 = $this->getCollection($collectionName);
		$provider2 = $this->initSameProvider($this->provider);
		$collection21 = $this->getProviderCollection($provider2, $collectionName);

		echo '--------------------------';

		var_dump(spl_object_hash($collection12));
		var_dump(spl_object_hash($collection21));

		var_dump($collection12);
		var_dump($collection21);

		//		var_dump($this->provider->getStorageName());
		//		print_r($this->getProtectedProperty($this->provider, 'initializedCollections'));
		//		var_dump($provider2->getStorageName());
		//print_r(		$this->getProtectedProperty($provider2, 'initializedCollections'));

		$this->assertEquals($expectedValue, $collection12->get($key));
		$this->assertEquals($expectedValue, $collection21->get($key));

		$collection11->delete($key);

		$this->assertNull($collection11->get($key));
		$this->assertNull($collection12->get($key));
		$this->assertNull($this->getCollection($collectionName)->get($key));
		$this->assertNull($this->getProviderCollection($this->initProvider(), $collectionName)->get($key));
	}

	/**
	 * @param $collectionName
	 * @app provideCollectionsNames
	 * @return void
	 */
	public function testSameSetGetDeleteAfterDestruct($collectionName) {
		$this->markTestSkipped('Fix it');
		return;
		$key = 'asd';
		$collection = $this->getCollection($collectionName);
		$this->assertNull($collection->get($key));
		$expectedValue = $this->generateStoreValue();
		$collection->set($key, $expectedValue);

		unset($collection);

		$collection2 = $this->getCollection($collectionName);

		$this->assertEquals($expectedValue, $collection2->get($key));
		$this->assertEquals($expectedValue, $this->getProviderCollection($this->initProvider(), $collectionName)->get($key));

		$collection2->delete($key);

		unset($collection2);

		$this->assertNull($this->getCollection($collectionName)->get($key));
		$this->assertNull($this->getProviderCollection($this->initProvider(), $collectionName)->get($key));
	}
}