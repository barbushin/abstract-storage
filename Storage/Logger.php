<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Logger {

	protected $lastLogIndex = 0;
	protected $transactionsStartsStack = array();
	protected $logEntries = array();

	protected function getNewLogIndex() {
		return ++$this->lastLogIndex;
	}

	public function startTransaction() {
		array_push($this->transactionsStartsStack, array($this->getNewLogIndex(), microtime(true)));
	}

	protected function getLastTransactionInfo() {
		if(!$this->transactionsStartsStack) {
			throw new Exception('Count of log transaction start and commit mismatch');
		}
		return array_pop($this->transactionsStartsStack);
	}

	public function log(Storage_Logger_Entry $log) {
		$this->logEntries[$this->getNewLogIndex()] = $log;
		$this->flushEntriesToLog();
	}

	public function logTransaction(Storage_Logger_Entry $log) {
		list($logIndex, $time) = $this->getLastTransactionInfo();
		$log->time = microtime(true) - $time;
		$this->logEntries[$logIndex] = $log;
		$this->flushEntriesToLog();
	}

	public function flushEntriesToLog() {
		if($this->logEntries) {
			ksort($this->logEntries);
			$this->logEntries($this->logEntries);
			$this->logEntries = array();
		}
	}

	/**
	 * @abstract
	 * @param Storage_Logger_Entry[] $logEntries
	 * @return void
	 */
	abstract protected function logEntries($logEntries);

	public function __destruct() {
		$this->flushEntriesToLog();
	}
}
