<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObject_Mongo extends Storage_KeyObject {

	/**
	 * @var MongoCollection
	 */
	protected $mongoCollection;

	/**
	 * @var Storage_KeyValue_Mongo|null
	 */
	protected $keyValueMongoStorage;

	protected $keyName;

	public function __construct(MongoCollection $mongoCollection, array $propertiesNames = array(), $keyProperty = '_id') {
		$this->mongoCollection = $mongoCollection;
		$this->idName = $keyProperty;
		$this->setStorageName($this->mongoCollection->getName());
		parent::__construct($propertiesNames);
	}

	protected function _find(Storage_EqualsCriteria $criteria) {
		return iterator_to_array($this->mongoCollection->find($criteria->getPropertiesValues()));
	}

	protected function _deleteByCriteria(Storage_EqualsCriteria $criteria) {
		$this->mongoCollection->remove($criteria->getPropertiesValues());
	}

	/**
	 * @param $key
	 * @return void
	 */
	protected function _get($key) {
		return $this->getKeyValueCollection()->get($key);
	}

	protected function _set($key, $object) {
		return $this->getKeyValueCollection()->set($key, $object);
	}

	protected function _delete($key) {
		return $this->getKeyValueCollection()->delete($key);
	}

	/**
	 * @return Storage_KeyValue_Mongo
	 */
	protected function getKeyValueCollection() {
		if(!$this->keyValueMongoStorage) {
			$this->keyValueMongoStorage = new Storage_KeyValue_Mongo($this->mongoCollection);
			if($this->getLogger()) {
				$this->keyValueMongoStorage->setLogger($this->getLogger());
			}
		}
		return $this->keyValueMongoStorage;
	}

	protected function handleStoredValue(&$object, $key) {
		parent::handleStoredValue($object, $key);
		if(isset($object['_id'])) {
			unset($object['_id']);
		}
	}
}
