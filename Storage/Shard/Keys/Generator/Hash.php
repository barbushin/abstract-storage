<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_Keys_Generator_Hash extends Storage_Shard_Keys_Generator {

	const IS_RANDOM = false;

	protected $indexMin;
	protected $indexMax;
	protected $isKeyNumeric;

	public function __construct($indexMin, $indexMax, $isKeyNumeric = false) {
		$this->indexMin = $indexMin;
		$this->indexMax = $indexMax;
		$this->isKeyNumeric = $isKeyNumeric;
	}

	public function getIndexMin() {
		return $this->indexMin;
	}

	public function getIndexMax() {
		return $this->indexMax;
	}

	public function generateId($key) {
		return $this->indexMin + abs(($this->isKeyNumeric ? $key : crc32($key)) % ($this->indexMax - $this->indexMin + 1));
	}

	public function getAllIds() {
		return range($this->indexMin, $this->indexMax);
	}
}