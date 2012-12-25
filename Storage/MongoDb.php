<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_MongoDb extends Storage_Abstract implements Storage_KeyValueProvider, Storage_KeyObjectProvider {

	protected $host;
	protected $dbName;
	protected $connectionSettings = array(
		'connect' => true,
		'persist' => 'hero', // TODO: MED ask what is this
	);

	/**
	 * @var MongoDB
	 */
	protected $mongoDb;

	public function __construct($host, $dbName, array $connectionSettings = array(), Storage_Logger $logger = null) {
		if(isset(MongoCursor::$timeout)) {
			MongoCursor::$timeout = -1; // TODO: MED check why without this option it ->find method throws CusrsorTimeout exception
		}
		$this->host = $host;
		$this->dbName = $dbName;
		if($connectionSettings) {
			$this->connectionSettings = $connectionSettings;
		}
		$this->mongoDb = $this->initMongoConnection();
		if($logger) {
			$this->setLogger($logger);
		}
	}

	/**
	 * @return MongoDB
	 */
	protected function initMongoConnection() {
		try {
			$hostOrIp = class_exists('HostsWrapper', true) ? HostsWrapper::forceIp($this->host) : $this->host;
			$mongoConnection = new Mongo('mongodb://' . $hostOrIp . ':' . 27017, $this->connectionSettings);
			return $mongoConnection->selectDB($this->dbName);
		}
		catch(Exception $e) {
			throw new Storage_ConnectionFailed('Connection to MongoDb failed with error: ' . $e->getMessage());
		}
	}

	/**
	 * @param $name
	 * @return Storage_KeyValue_Mongo
	 */
	public function getKeyValueCollection($name) {
		$collection = new Storage_KeyValue_Mongo($this->mongoDb->selectCollection($name));
		if($this->getLogger()) {
			$collection->setLogger($this->getLogger());
		}
		return $collection;
	}

	/**
	 * @param $name
	 * @param array $propertiesNames
	 * @return Storage_KeyObject_Mongo
	 */
	public function getKeyObjectCollection($name, array $propertiesNames = array()) {
		$collection = new Storage_KeyObject_Mongo($this->mongoDb->selectCollection($name));
		if($this->getLogger()) {
			$collection->setLogger($this->getLogger());
		}
		if($propertiesNames) {
			$collection->setPropertiesNames($propertiesNames);
		}
		return $collection;
	}
}
