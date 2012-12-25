<?php

abstract class Test_Storage_Provider_Abstract extends Test_Case {

	const PROVIDED_STORAGE_CLASS = 'Storage_Abstract';

	protected $provider;

	protected $collectionsNames;

	protected function __init() {
		parent::__init();
		$this->provider = $this->initProvider();
		$this->collectionsNames = $this->generateCollectionsNames();
	}

	protected function tearDown() {
		parent::tearDown();
		foreach($this->collectionsNames as $collectionName) {
			$collection = $this->getCollection($collectionName);
			if($collection instanceof Storage_Global) {
				$collection->clear();
			}
		}
	}

	abstract protected function initProvider();

	protected function initSameProvider($provider) {
		return $this->initProvider();
	}

	/**
	 * @abstract
	 * @param $provider
	 * @param $collectionName
	 * @return Storage_Abstract
	 */
	abstract protected function getProviderCollection($provider, $collectionName);

	protected function generateCollectionsNames() {
		$collectionsNames = array();
		for($i = 0; $i < 3; $i++) {
			$collectionsNames[] = 'test_provider_' . mt_rand();
		}
		return $collectionsNames;
	}

	public function provideCollectionsNames() {
		return $this->generateProviderCalls($this->generateCollectionsNames());
	}

	/**
	 * @param $collectionName
	 * @app provideCollectionsNames
	 * @return void
	 */
	public function testReturnedOjectsAreStorages($collectionName) {
		$this->assertInstanceOf(static::PROVIDED_STORAGE_CLASS, $this->getCollection($collectionName));
	}

	/**
	 * @param $collectionName
	 * @app provideCollectionsNames
	 * @return void
	 */
	public function testReturnedStoragesAreEmpty($collectionName) {
		$collection = $this->getCollection($collectionName);
		if($collection instanceof Storage_Global) {
			$this->assertEquals(array(), $collection->getAll());
		}
		else {
			$this->markTestSkipped('Collection storage does not implement Storage_Global interface');
		}
	}
}