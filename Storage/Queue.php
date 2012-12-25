<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Queue extends Storage_Abstract {

	protected $dataSizeLimit;

	abstract protected function _push($data);

	abstract protected function _pop();

	public function __construct($dataSizeLimit = null) {
		$this->dataSizeLimit = $dataSizeLimit;
	}

	public function getDataSizeLimit() {
		return $this->dataSizeLimit;
	}

	public function popAll() {
		$dataArray = array();
		while(($data = $this->pop()) !== null) {
			$dataArray[] = $data;
		}
		return $dataArray;
	}

	protected function handleStoreData(&$data) {
		$this->handleScalarValue($value);
		if(!is_scalar($value)) {
			throw new Storage_WrongRequest('Only scalar data types are allowed to store, "' . gettype($value) . '" given');
		}
		if($this->dataSizeLimit && strlen($data) > $this->dataSizeLimit) {
			throw new Storage_WrongRequest('Data size is limited to ' . $this->dataSizeLimit . '" bytes');
		}
	}

	protected function handleStoredData(&$data) {
		$this->handleScalarValue($value);
		if(!is_scalar($value)) {
			throw new Storage_WrongRequest('Only scalar data types are allowed to store, "' . gettype($value) . '" given');
		}
	}

	/**
	 *
	 * @throws Exception
	 * @internal param $key
	 * @return mixed|null
	 */
	final public function pop() {
		try {
			$this->logStart();
			$data = $this->_pop();
			if(static::HANDLE_STORE_DATA && $data !== null) {
				$this->handleStoredData($data);
			}
			$this->logCommit(__FUNCTION__, func_get_args(), $data);
			return $data;
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	final public function push($data) {
		try {
			$this->logStart();
			if(static::HANDLE_STORE_DATA) {
				$this->handleStoreData($data);
			}
			$rollback = $this->isTransactional() ? new Transaction_Callback(function(Storage_Queue $storage) {
				$storage->pop();
			}, array($this)) : null;

			$this->_push($data);

			if($rollback) {
				Transaction_Handler::getInstance()->addRollback($rollback);
			}

			$this->logCommit(__FUNCTION__, func_get_args());
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}
}
