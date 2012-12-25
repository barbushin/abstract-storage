<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Logger_File extends Storage_Logger {

	/**
	 * Must be absolute path to work in __destruct
	 * @var string
	 */
	protected $absFilePath;
	protected $clear;

	public function __construct($filePath, $clear = false) {
		$this->initLogFile($filePath, $clear);
		$this->absFilePath = realpath($filePath);
	}

	protected function initLogFile($filePath, $clear) {
		if($clear || !file_exists($filePath)) {
			$created = @file_put_contents($filePath, '');
			if($created === false) {
				throw new Exception('Log file "' . $filePath . '" can\'t be created');
			}
		}
	}

	/**
	 * @param Storage_Logger_Entry[] $logEntries
	 * @return void
	 */
	protected function logEntries($logEntries) {
		$logString = '';
		foreach($logEntries as $log) {
			$logString .= $this->getLogString($log) . "\n";
		}
		file_put_contents($this->absFilePath, $logString, FILE_APPEND | LOCK_EX);
	}

	/**
	 * @param Storage_Logger_Entry $log
	 * @return string
	 */
	protected function getLogString(Storage_Logger_Entry $log) {
		$logArgs = array();
		foreach($log->arguments as $arg) {
			$logArgs[] = $this->safeJsonEncode($arg);
		}
		return implode("\t", array(
			$log->storageName,
			$log->action . '(' . implode(', ', $logArgs) . ')',
			$log->getTimeInMilliseconds(),
			$this->safeJsonEncode($log->result),
			$log->exception ? get_class($log->exception) . ': ' . $log->exception->getMessage() : null,
		));
	}

	protected function safeJsonEncode($data) {
		$utfEncodeFunc = function(&$input) {
			if(is_scalar($input)) {
				$input = iconv('UTF-8', 'UTF-8//IGNORE', $input);
			}
		};
		if(is_array($data)) {
			array_walk_recursive($data, $utfEncodeFunc);
		}
		else {
			$utfEncodeFunc($data);
		}
		$result = is_string($data) ? $data : @json_encode($data);
		return strlen($result) > 100 ? substr($result, 0, 97) . '...' : $result;
	}
}
