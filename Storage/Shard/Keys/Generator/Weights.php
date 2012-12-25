<?php

/**
 * @see https://github.com/barbushin/abstract-storage
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class Storage_Shard_Keys_Generator_Weights extends Storage_Shard_Keys_Generator {

	const IS_RANDOM = true;

	protected $weightsSum;
	protected $weightsSums;
	protected $storagesWeights;

	public function __construct(array $storagesWeights) {
		asort($storagesWeights);
		$this->storagesWeights = $storagesWeights;
		foreach($storagesWeights as $id => $weight) {
			$this->weightsSum += $weight;
			$this->weightsSums[$id] = $this->weightsSum;
		}
	}

	public function generateId($key) {
		$rndWeight = mt_rand(1, $this->weightsSum);
		foreach($this->weightsSums as $id => $sum) {
			if($sum >= $rndWeight) {
				return $id;
			}
		}
		throw new Exception('WTF?');
	}

	public function getAllIds() {
		return array_keys($this->weightsSums);
	}

	public static function getWeightsFromMultiArray(array $array, $weightIndex = 'weight') {
		$storagesWeights = array();
		foreach($array as $id => $object) {
			if(isset($object[$weightIndex])) {
				$storagesWeights[$id] = $object[$weightIndex];
			}
		}
		if(!$storagesWeights) {
			throw new Exception('There is no items with key "' . $weightIndex . '" in array');
		}
		return $storagesWeights;
	}
}