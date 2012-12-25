<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Exception extends Exception {

}

class Storage_WrongResponse extends Storage_Exception {

}

class Storage_ConnectionFailed extends Storage_WrongResponse {

}

// TODO: MED or may be just use Storage_ConnectionFailed instead of this one?
class Storage_WrongRequest extends Storage_Exception {

}

class Storage_DataCorrupt extends Storage_Exception {

	/** @var Storage_Abstract */
	protected $storage;

	public function __construct(Storage_Abstract $storage, $message = null) {
		$this->storage = $storage;
		parent::__construct('There is corrupt data in storage ' . $storage->getStorageName() . '. ' . $message);
	}

	public function getStorage() {
		return $this->storage;
	}
}