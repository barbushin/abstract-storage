<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_KeyValue_RedisCached extends Storage_KeyValue_Cached {

	/**
	 * @var Storage_KeyValue_RedisPrefixed
	 */
	protected $cacheStorage;

	protected $isNumericKeys;
	protected $lastFlushTimeKey;

	public function __construct(Storage_KeyValue $baseStorage, Storage_KeyValue_RedisPrefixed $prefixedRedisCacheStorage, $lastFlushTimeKey = null, $baseStorageFullSyncMode = false, $cacheStoragePassiveMode = false, $isNumericKeys = true) {
		$this->isNumericKeys = $isNumericKeys;
		$this->lastFlushTimeKey = $lastFlushTimeKey ? : __METHOD__ . ':' . $this->baseStorage->getStorageName() . '-' . $this->cacheStorage->getStorageName();
		parent::__construct($baseStorage, $prefixedRedisCacheStorage, $baseStorageFullSyncMode, $cacheStoragePassiveMode);
	}

	public function flush($maxClearInterval = 43200, $keysPerIteration = 1000, Bench_Progress $progress = null) {
		/** @var $redisStorage Storage_KeyValue_Redis */
		$redisStorage = $this->cacheStorage->getStorage();
		$redisConnection = $redisStorage->getRedisConnection();

		$currentFlushTime = time();
		$lastFlushTime = $redisConnection->get($this->lastFlushTimeKey);
		$maxClearTime = time() - $maxClearInterval;
		$isClearEnabled = $maxClearTime < $lastFlushTime;

		$cachedKeys = $this->cacheStorage->getAllKeys();

		// draft fix because of fucking MySQL equals 123abc to 123
		if($this->isNumericKeys) {
			$badKeys = array();
			foreach($cachedKeys as $key) {
				if(!is_numeric($key)) {
					$this->cacheStorage->delete($key);
				}
			}
			if($badKeys) {
				Errors_Handler::getInstance()->handleException(new Storage_ShardKeysNotFound($badKeys));
			}
		}

		$totalFlushed = 0;
		$totalCleared = 0;

		if($progress) {
			$progress->setMaxValue(count($cachedKeys));
		}

		for($offset = 0; $keys = array_slice($cachedKeys, $offset, $keysPerIteration); $offset += $keysPerIteration) {
			$flushKeys = array();
			$clearKeys = array();
			foreach($keys as $key) {
				$lastUpdateTime = time() - $redisConnection->getIdleTime($this->cacheStorage->getStorageKey($key));
				if($lastUpdateTime > $lastFlushTime) {
					$flushKeys[] = $key;
				}
				elseif($isClearEnabled && $lastUpdateTime < $maxClearTime) {
					$clearKeys[] = $key;
				}
			}
			if($flushKeys) {
				try {
					$flushTried = false; // to prevent looping
					REDIS_CACHED_FLUSH_KEYS: // sorry about that, but it's just shortest way :)
					if(!$flushTried) {
						$this->baseStorage->sets(array_filter($this->cacheStorage->gets($flushKeys)));
						$totalFlushed += count($flushKeys);
					}
				}
				catch(Storage_ShardKeysNotFound $exception) {
					$flushTried = true;
					Errors_Handler::getInstance()->handleException($exception); // not very good to call it there
					$this->cacheStorage->mDelete($exception->getKeys());
					GOTO REDIS_CACHED_FLUSH_KEYS;
				}
				if($clearKeys) {
					$this->cacheStorage->mDelete($clearKeys);
					$totalCleared += count($clearKeys);
				}
			}

			if($progress) {
				$progress->setInfo('FLUSHED: ' . $totalFlushed . ' CLEARED: ' . $totalCleared)->handleDiff(count($keys));
			}
		}
		$redisConnection->set($this->lastFlushTimeKey, $currentFlushTime);
	}
}
