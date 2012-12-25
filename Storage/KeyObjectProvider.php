<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
interface Storage_KeyObjectProvider {

	/**
	 * @abstract
	 * @param $name
	 * @return Storage_KeyObject
	 */
	public function getKeyObjectCollection($name, array $propertiesNames = array());
}
