<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Queue_Memory extends Storage_Queue {

	protected $queue = array();

	protected function _push($data) {
		array_push($this->queue, $data);
	}

	protected function _pop() {
		return array_shift($this->queue);
	}

	public function popAll() {
		$dataArray = $this->queue;
		$this->queue = array();
		return $dataArray;
	}
}
