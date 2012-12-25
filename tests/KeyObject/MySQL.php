<?php

/*

CREATE TABLE `key_object` (
  `id` int(14) unsigned NOT NULL AUTO_INCREMENT,
  `int` int(14) NOT NULL,
  `zero` int(14) NOT NULL,
  `string` varchar(1000) NOT NULL,
  `string2` varchar(1000) NOT NULL,
  `stringCommon` varchar(1000) NOT NULL,
  `boolTrue` tinyint(1) NOT NULL,
  `boolFalse` tinyint(1) NOT NULL,
  `null` varchar(1000) DEFAULT NULL,
  `emptyString` varchar(1000) DEFAULT NULL,
  `array` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2145238884 DEFAULT CHARSET=utf8

*/
class Test_Storage_KeyObject_MySQL extends Test_Storage_KeyObject {

	/**
	 * @return Storage_KeyObject
	 */
	protected function initStorage(array $propertiesNames = array()) {
		return new Storage_KeyObject_MySQL(new Storage_MySQL('localhost', 'root', '', 'test'), 'key_object');
	}
}

