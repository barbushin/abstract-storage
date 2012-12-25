<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
abstract class Storage_Shard_Keys_Generator {

	const IS_RANDOM = true;

	abstract public function generateId($key);

	abstract public function getAllIds();
}
