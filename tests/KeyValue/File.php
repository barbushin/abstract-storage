<?php

class Test_Storage_KeyValue_File extends Test_Storage_KeyValue {

	/**
	 * @return Storage_KeyValue
	 */
	protected function initStorage() {
		return new Storage_KeyValue_File(\ENGINE\TMP_DIR . '/test_file_storage.dat');
	}
}

