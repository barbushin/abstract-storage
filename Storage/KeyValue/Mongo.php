<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_Mongo extends Storage_KeyValue implements Storage_Global {

	const OBJECT_VALUE_KEY = '__value';
	const OBJECT_ID_KEY = '_id';

	/**
	 * @var MongoCollection
	 */
	protected $mongoCollection;
	protected $keyName;

	protected function handleStoredValue(&$value, $key) {
		if(is_array($value) && array_key_exists(static::OBJECT_VALUE_KEY, $value)) {
			$value = $value[static::OBJECT_VALUE_KEY];
		}
		if(is_array($value) && isset($value[static::OBJECT_ID_KEY])) {
			unset($value[static::OBJECT_ID_KEY]);
		}
		parent::handleStoredValue($value, $key);
	}

	protected function handleStoreValue(&$value, $key = null) {
		parent::handleStoreValue($value, $key);
		if(!is_array($value)) {
			$value = array(static::OBJECT_VALUE_KEY => $value);
		}
		// TODO: MED check if you should also use OBJECT_ID_KEY there
		$value[$this->keyName] = $key;
	}

	public function __construct(MongoCollection $mongoCollection, $keyName = null) {
		$this->mongoCollection = $mongoCollection;
		if(!$keyName) {
			$keyName = static::OBJECT_ID_KEY;
		}
		$this->setKeyName($keyName);
		$this->setStorageName($this->mongoCollection->getName());
	}

	public function setKeyName($keyName) {
		$this->keyName = $keyName;
	}

	protected function _get($key) {
		return $this->mongoCollection->findOne(array($this->keyName => $key));
	}

	protected function _set($key, $value) {
		try {
			$this->mongoCollection->save($value);
		}
		catch(MongoCursorException $e) {
			throw new Storage_WrongRequest(get_class($e) . ': ' . $e->getMessage());
		}
	}

	protected function _delete($key) {
		$this->mongoCollection->remove(array($this->keyName => $key));
	}

	/*
	 // TODO: uncomment and check why it not works when very needed
	 protected function _gets(array $keys) {
		 $ids = array();
		 foreach($keys as $key) {
			 // TODO: MED check if this is really required
			 $ids[] = new MongoId($key);
		 }

		 $orConditionArray = array();
		 foreach($keys as $key) {
			 $orConditionArray[] = array($this->keyName => $key);
		 }
		 return iterator_to_array($this->mongoCollection->find(array('$or' => $orConditionArray)));
	 }
 */

	/*
	// TODO: uncomment and check why it not works when very needed
	protected function _increment($key, $sum) {
		$this->mongoCollection->update(array($this->keyName => $key), array('$inc' => array(static::OBJECT_VALUE_KEY => $sum)), array('upsert' => true, 'safe' => true));
	}
	*/
	public function getAll() {
		return iterator_to_array($this->mongoCollection->find());
	}

	public function clear() {
		$this->mongoCollection->drop();
	}
}
