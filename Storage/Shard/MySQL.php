<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_MySQL extends Storage_Shard {

	/**
	 * @param $config
	 * @return Storage_MySQL
	 */
	protected function initStorage($config) {
		return new Storage_MySQL($config['host'], $config['login'], $config['password'], $config['db']);
	}
}
