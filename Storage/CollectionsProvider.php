<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_CollectionsProvider extends Storage_Abstract {

	protected static $collectionsNames = array();

	protected $initializedCollections = array();

	abstract protected function initCollection($collection);

	public function isCollection($collectionName) {
		return in_array($collectionName, $this->getCollectionsNames());
	}

	public function getCollectionsNames() {
		return static::$collectionsNames;
	}

	/**
	 * @param $collectionName
	 * @return Storage_Abstract
	 */
	public function getCollection($collectionName) {
		if(!$this->isCollection($collectionName)) {
			throw new Storage_WrongRequest('Unknown collection name "' . $collectionName . '"');
		}
		if(!array_key_exists($collectionName, $this->initializedCollections)) {
			$this->initializedCollections[$collectionName] = $this->initCollection($collectionName);
		}
		return $this->initializedCollections[$collectionName];
	}
}
