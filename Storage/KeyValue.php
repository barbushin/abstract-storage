<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_KeyValue extends Storage_Abstract {

	/**
	 * @abstract
	 * @param $key
	 * @return void
	 */
	abstract protected function _get($key);

	abstract protected function _set($key, $value);

	abstract protected function _delete($key);

	protected function validateStoreKey($key) {
	}

	protected function handleStoreValue(&$value, $key = null) {
		$this->handleScalarValue($value);
		if(!is_scalar($value) && !is_array($value)) {
			throw new Storage_WrongRequest('Only scalar and array values allowed to store, "' . gettype($value) . '" given');
		}
	}

	protected function handleStoredValue(&$value, $key) {
	}

	public function generateNewKey() {
		$uid = strtoupper(substr(md5(mt_rand()) . md5(mt_rand()), 10, 36));
		$uid[8] = $uid[13] = $uid[18] = $uid[23] = '-';
		return $uid;
	}

	/**
	 * @throws Exception
	 * @param $key
	 * @return mixed|null
	 */
	public function get($key) {
		try {
			$this->logStart();
			static::HANDLE_STORE_DATA && $this->validateStoreKey($key);
			$result = $this->_get($key);
			if(static::HANDLE_STORE_DATA && $result !== null) {
				$this->handleStoredValue($result, $key);
			}
			$this->logCommit(__FUNCTION__, func_get_args(), $result);
			return $result;
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	public function gets($keys) {
		try {
			$this->logStart();

			if(static::HANDLE_STORE_DATA) {
				foreach($keys as $key) {
					$this->validateStoreKey($key);
				}
			}

			$result = $this->_gets($keys);

			foreach($keys as $key) {
				if(!isset($result[$key])) {
					$result[$key] = null;
				}
				elseif(static::HANDLE_STORE_DATA) {
					$this->handleStoredValue($result[$key], $key);
				}
			}

			$this->logCommit(__FUNCTION__, func_get_args(), $result);
			return $result;
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	public function increments($keysIncrements) {
		foreach($keysIncrements as $key => $sum) {
			$this->increment($key, $sum);
		}
	}

	public function insert($value) {
		$keys = $this->inserts(array($value));
		return current($keys);
	}

	public function inserts(array $values) {
		if($values) {
			try {
				$this->logStart();
				if(static::HANDLE_STORE_DATA && $values) {
					foreach($values as $key => &$value) {
						$this->handleStoreValue($value, $key);
					}
				}
				$keys = $this->_inserts($values);

				$this->logCommit(__FUNCTION__, func_get_args());
				return $keys;
			}
			catch(Exception $exception) {
				$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
				throw $exception;
			}
		}
		return array();
	}

	protected function _inserts($values) {
		$keysValues = array();
		foreach($values as $value) {
			$keysValues[$this->generateNewKey()] = $value;
		}
		$this->sets($keysValues);
		return array_keys($keysValues);
	}

	public function set($key, $value) {
		try {
			$this->logStart();
			if(static::HANDLE_STORE_DATA) {
				$this->validateStoreKey($key);
				$this->handleStoreValue($value, $key);
			}

			$rollback = $this->isTransactional() ? new Transaction_Callback(function($key, $oldValue, Storage_KeyValue $storage) {
				$oldValue === null ? $storage->delete($key) : $storage->set($key, $oldValue);
			}, array($key, $this->get($key), $this)) : null;

			$this->_set($key, $value);

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

	public function sets(array $keysValues) {
		if($keysValues) {
			try {
				$this->logStart();
				if(static::HANDLE_STORE_DATA) {
					foreach($keysValues as $key => &$value) {
						$this->validateStoreKey($key);
						$this->handleStoreValue($value, $key);
					}
				}
				if($keysValues) {
					if($this->isTransactional()) {
						Transaction_Handler::getInstance()->addRollback(new Transaction_Callback(function($oldKeysValues, Storage_KeyValue $storage) {
							foreach($oldKeysValues as $key => $value) {
								if($value === null) {
									unset($oldKeysValues[$key]);
									$storage->delete($key);
								}
							}
							if($oldKeysValues) {
								$storage->sets($oldKeysValues);
							}
						}, array($this->gets(array_keys($keysValues)), $this)));
					}

					$this->_sets($keysValues);
				}
				$this->logCommit(__FUNCTION__, func_get_args());
			}
			catch(Exception $exception) {
				$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
				throw $exception;
			}
		}
	}

	public function delete($key) {
		try {
			$this->logStart();
			static::HANDLE_STORE_DATA && $this->validateStoreKey($key);
			$rollback = $this->isTransactional() ? new Transaction_Callback(function($key, $oldValue, Storage_KeyValue $storage) {
				$storage->set($key, $oldValue);
			}, array($key, $this->get($key), $this)) : null;

			$this->_delete($key);

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

	public function mDelete(array $keys) {
		try {
			$this->logStart();
			$rollback = $this->isTransactional() ? new Transaction_Callback(function($oldKeysValues, Storage_KeyValue $storage) {
				$storage->sets($oldKeysValues);
			}, array(array_filter($this->gets($keys)), $this)) : null;

			$this->_mDelete($keys);

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

	protected function _mDelete(array $keys) {
		foreach($keys as $key) {
			$this->_delete($key);
		}
	}

	public function increment($key, $sum) {
		try {
			$this->logStart();
			if(static::HANDLE_STORE_DATA) {
				if(!is_numeric($sum)) {
					throw new Storage_WrongRequest('Increment value must be numeric, "' . gettype($sum) . '" given: ');
				}
				$this->validateStoreKey($key);
			}
			if($sum) {
				$rollback = $this->isTransactional() ? new Transaction_Callback(function($key, $decrement, Storage_KeyValue $storage) {
					$storage->increment($key, $decrement * -1);
				}, array($key, $sum, $this)) : null;

				$this->_increment($key, $sum);

				if($rollback) {
					Transaction_Handler::getInstance()->addRollback($rollback);
				}
				$this->logCommit(__FUNCTION__, func_get_args());
			}
		}
		catch(Exception $exception) {
			$this->logCommit(__FUNCTION__, func_get_args(), null, $exception);
			throw $exception;
		}
	}

	protected function _gets(array $keys) {
		$results = array();
		foreach($keys as $key) {
			$results[$key] = $this->get($key);
		}
		return $results;
	}

	protected function _sets(array $keysValues) {
		foreach($keysValues as $key => $value) {
			$this->set($key, $value);
		}
	}

	protected function _increment($key, $sum) {
		$value = $this->get($key);
		if(!$value) {
			$value = 0;
		}
		elseif(!is_numeric($value)) {
			throw new Storage_WrongRequest('Stored value is not numeric and can\'t be incremented');
		}
		$value += $sum;
		$this->set($key, $value);
	}
}
