<?php
namespace itsoneiota\cache;
/**
 * A cached view fragment.
 */
class CacheValue {
	protected $value;
	protected $timestamp;

	public function __construct($value, \DateTime $timestamp) {
		$this->value = $value;
		$this->timestamp = $timestamp;
	}

	public function __get($name){
		return property_exists($this, $name) ? $this->$name : NULL;
	}
}
