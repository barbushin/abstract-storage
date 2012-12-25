<?php

abstract class Test_Storage_Provider_KeyObject extends Test_Storage_Provider_KeyValue {

	const PROVIDED_STORAGE_CLASS = 'Storage_KeyObject';

	/**
	 * @var Storage_KeyObjectProvider
	 */
	protected $provider;

	/**
	 * @abstract
	 * @param $collectionName
	 * @return Storage_KeyObject
	 */
	protected function getProviderCollection($provider, $collectionName) {
		return $provider->getKeyObjectCollection($collectionName);
	}

	protected function generateStoreValue() {
		return array(mt_rand() => mt_rand());
	}
}