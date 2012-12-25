<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
interface Storage_KeyValueProvider {

	/**
	 * @abstract
	 * @param $name
	 * @return Storage_KeyValue
	 */
	public function getKeyValueCollection($name);
}
