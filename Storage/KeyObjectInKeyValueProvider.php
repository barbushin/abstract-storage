<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyObjectInKeyValueProvider implements Storage_KeyObjectProvider {

	const DATA_GZIP_LEVEL = 7;
	const ALERT_JSON_DATA_SIZE = 2000000;
	const MAX_JSON_DATA_SIZE = 10000000;

	/**
	 * @var Storage_KeyValue
	 */
	protected $storage;

	protected $dataKey;
	protected $gzipData;
	protected $name;
	protected $originalCollections;

	/**
	 * @var Storage_KeyObject_Memory[]
	 */
	protected $initializedCollections = array();

	/**
	 * @param Storage_KeyValue $storage
	 * @param $dataKey
	 * @param bool $gzipData
	 */
	public function __construct(Storage_KeyValue $storage, $dataKey, $gzipData = false) {
		$this->storage = $storage;
		$this->dataKey = $dataKey;
		$this->gzipData = $gzipData;
		$this->name = $storage->getStorageName() . '/' . $dataKey;
		$this->initializedCollections = Flyweighter::getSetRef('init' . __CLASS__ . $this->name, $this->initializedCollections);
		$this->originalCollections = Flyweighter::getSetRef('orig' . __CLASS__ . $this->name, $this->originalCollections);
	}

	public function getStorage() {
		return $this->storage;
	}

	public function getName() {
		return $this->name;
	}

	public function getDataKey() {
		return $this->dataKey;
	}

	protected function initCollections() {
		$collectionsJsonData = $this->storage->get($this->dataKey);
		if(!$collectionsJsonData) {
			$this->originalCollections = array();
		}
		else {
			if($this->gzipData) {
				$collectionsJsonData = @gzuncompress($collectionsJsonData);
				if($collectionsJsonData === false) {
					throw new Storage_Exception('Unzip cell failed in: ' . $this->name);
				}
			}

			if(strlen($collectionsJsonData) > static::ALERT_JSON_DATA_SIZE) {
				Errors_Handler::getInstance()->handleException(new Storage_DataCorrupt($this->storage, 'It\'s not critical, just alert notification. Data key: ' . $this->dataKey));
			}
			if(strlen($collectionsJsonData) > static::MAX_JSON_DATA_SIZE) {
				Errors_Handler::getInstance()->handleException(new Storage_DataCorrupt($this->storage, 'Data key: ' . $this->dataKey)); // TODO: add throwing and catching on top app layer
			}
			$this->originalCollections = @json_decode($collectionsJsonData, true);
			if($this->originalCollections === false) {
				throw new Storage_Exception('JSON decode cell failed in: ' . $this->name);
			}
			$this->initializedCollections = array();
		}
	}

	protected function saveCollections($force = false) {
		$collectionsChanged = false;
		foreach($this->initializedCollections as $collectionName => $initializedCollection) {
			if($initializedCollection->isChanged() || !isset($this->originalCollections[$collectionName])) {
				$this->originalCollections[$collectionName] = $initializedCollection->getAll();
				$collectionsChanged = true;
			}
		}
		if($force || $collectionsChanged) {
			$collectionsData = json_encode($this->originalCollections);
			if($this->gzipData) {
				$collectionsData = gzcompress($collectionsData, static::DATA_GZIP_LEVEL);
			}
			$this->storage->set($this->dataKey, $collectionsData);
		}
	}

	public function removeCollection($collectionName) {
		if(isset($this->initializedCollections[$collectionName])) {
			unset($this->initializedCollections[$collectionName]);
		}
		if(isset($this->originalCollections[$collectionName])) {
			unset($this->originalCollections[$collectionName]);
		}
		$this->saveCollections(true);
	}

	public function getKeyObjectCollection($name, array $propertiesNames = array()) {
		if($this->originalCollections === null) {
			$this->initCollections();
		}
		if(!isset($this->initializedCollections[$name])) {
			$collection = new Storage_KeyObject_Memory(isset($this->originalCollections[$name]) ? $this->originalCollections[$name] : array(), $propertiesNames);
			$collection->setStorageName($this->name . '/' . $name);
			$this->initializedCollections[$name] = $collection;
		}
		return $this->initializedCollections[$name];
	}

	public function __destruct() {
		$this->saveCollections();
	}
}