<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Logger_Entry {

	public $storageName;
	public $action;
	public $arguments = array();
	public $result;
	public $time;

	/**
	 * @var Exception
	 */
	public $exception;

	public function getTimeInMilliseconds($round = 2) {
		return $this->time ? round($this->time * 1000, $round) : null;
	}
}

