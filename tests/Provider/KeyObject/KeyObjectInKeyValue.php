<?php

class Test_Storage_Provider_KeyObject_KeyObjectInKeyValue extends Test_Storage_Provider_KeyObject {

	protected function initProvider($dataKey = null) {
		return new Storage_KeyObjectInKeyValueProvider(new Storage_KeyValue_MemoryShared(), $dataKey ? : mt_rand(), true);
	}

	protected function initSameProvider($provider) {
		return $this->initProvider($provider->getDataKey());
	}
}
