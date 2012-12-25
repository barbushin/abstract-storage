<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Abstract {

	const HANDLE_STORE_DATA = true;
	const IS_TRANSACTIONAL = true;

	protected $isTransactional;

	private $storageName;

	/**
	 * @var Storage_Logger|null
	 */
	protected $logger;

	public function getStorageName() {
		return get_class($this) . ($this->storageName ? '/' . $this->storageName : '');
	}

	public function setStorageName($name) {
		$this->storageName = $name;
	}

	public function setLogger(Storage_Logger $logger) {
		if($this->logger) {
			throw new Exception('Logger is already defined');
		}
		$this->logger = $logger;
	}

	protected function handleScalarValue(&$value) {
		if($value === null) {
			$value = '';
		}
		elseif(is_bool($value)) {
			$value = $value ? 1 : 0;
		}
	}

	protected function isTransactional() {
		return false && static::IS_TRANSACTIONAL && Transaction_Handler::getInstance()->isTransactionStarted(); // TODO: cut false && when transactional operations testing will be complete
	}

	protected function addRollback(Transaction_Callback $callback) {
		Transaction_Handler::getInstance()->addRollback($callback);
	}

	public function getLogger() {
		return $this->logger;
	}

	protected function logStart() {
		if($this->logger) {
			$this->logger->startTransaction();
		}
	}

	protected function logCommit($action, $arguments = array(), $result = null, Exception $exception = null) {
		if($this->logger) {
			$log = new Storage_Logger_Entry();
			$log->storageName = $this->getStorageName();
			$log->action = $action;
			$log->arguments = $arguments;
			$log->result = $result;
			$log->exception = $exception;
			$this->logger->logTransaction($log);
		}
	}

	public static function getKeyByWeight($keysWeights) {
		asort($keysWeights);
		$weightsSum = 0;
		$weightsSums = array();
		foreach($keysWeights as $key => $weight) {
			$weightsSum += $weight;
			$weightsSums[$key] = $weightsSum;
		}
		$rndWeight = mt_rand(1, $weightsSum);
		foreach($weightsSums as $key => $sum) {
			if($sum >= $rndWeight) {
				return $key;
			}
		}
		throw new Exception();
	}
}

class Storage_EqualsCriteria {

	protected $propertiesValues = array();

	public function __construct(array $propertiesValues = array()) {
		$this->propertiesValues = $propertiesValues;
	}

	public function addEqualCriteria($property, $value) {
		$this->propertiesValues[$property] = $value;
		return $this;
	}

	public function getPropertiesValues() {
		return $this->propertiesValues;
	}
}

require_once(__DIR__ . '/Exception.php');
