<?php

abstract class Test_Storage_Abstract extends Test_Case {

	/**
	 * @var Storage_KeyValue
	 */
	protected $storage;

	/**
	 * @abstract
	 * @return Storage_KeyValue
	 */
	abstract protected function initStorage();

	public function __init() {
		parent::__init();
		$this->storage = $this->initStorage();
		$this->storage->setLogger(new Storage_Logger_File(\ENGINE\DATA_DIR . '/data.log', true));
	}

	protected function tearDown() {
		parent::tearDown();
		if($this->storage instanceof Storage_Global) {
			$this->storage->clear();
		}
	}
}