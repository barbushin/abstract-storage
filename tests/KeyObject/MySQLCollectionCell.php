<?php

/*

CREATE TABLE `key_object_serialized_cell` (
  `id` varchar(255) NOT NULL,
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

*/
class Test_Storage_KeyObject_MySQLCollectionCell extends Test_Storage_KeyObject {

	/**
	 * @return Storage_KeyObject
	 */
	protected function initStorage(array $propertiesNames = array()) {
		return new Storage_KeyObject_MySQLCollectionCell(new Storage_MySQL('localhost', 'root', '', 'test'), 'key_object_collection_in_cell', mt_rand());
	}
}

