<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Queue_Optimized extends Storage_Queue {

	const HANDLE_STORE_DATA = false;
	const IS_TRANSACTIONAL = false;
	const DATA_SEPARATOR = '[^]{#}%!~`<@>';

	/** @var Storage_Queue_Memory */
	protected $cacheQueue;

	/** @var Storage_Queue */
	protected $storeQueue;

	public function __construct(Storage_Queue $storeQueue) {
		$this->storeQueue = $storeQueue;
		$this->cacheQueue = new Storage_Queue_Memory();
		parent::__construct($storeQueue->getDataSizeLimit());
	}

	protected function _push($data) {
		$this->cacheQueue->push($data);
	}

	protected function flushCacheData() {
		$dataPack = null;
		foreach($this->cacheQueue->popAll() as $data) {
			if($this->dataSizeLimit && strlen($dataPack) + strlen($data) + strlen(static::DATA_SEPARATOR) > $this->dataSizeLimit) {
				$this->storeQueue->push($dataPack);
				$dataPack = null;
			}
			else {
				$dataPack .= ($dataPack ? static::DATA_SEPARATOR : '') . $data;
			}
		}
		if($dataPack !== null) {
			$this->storeQueue->push($dataPack);
		}
	}

	protected function _pop() {
		$data = $this->cacheQueue->pop();
		if($data === null) {
			$packetData = $this->storeQueue->pop();
			if($packetData !== null) {
				$dataArray = explode(static::DATA_SEPARATOR, $packetData);
				$data = array_pop($dataArray);
				foreach($dataArray as $data) {
					$this->cacheQueue->push($data);
				}
			}
		}
		return $data;
	}

	public function __destruct() {
		$this->flushCacheData();
	}
}
