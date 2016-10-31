<?php
namespace itsoneiota\cache;
/**
 * Wrapper for a simple array.
 */
class InMemoryCounter extends CacheWrapper implements Counter {

	protected $contents = array();

	/**
	 * Constructor.
	 *
	 */
	public function __construct(){
		// No-op.
	}

	/**
	 * Retrieve an item.
	 *
	 * @param string $key The key of the item to retrieve.
	 * @return mixed Returns the value stored in the cache or NULL otherwise.
	 */
	public function get($key) {
		return is_array($key) ? $this->multiGet($key) : $this->singleGet($key);
  	}

  	protected function singleGet($key){
  		return array_key_exists($this->mapKey($key), $this->contents) ? $this->unmapValue($this->contents[$this->mapKey($key)]) : NULL;
  	}

  	protected function multiGet(array $keys){
  		$results = [];
  		foreach ($keys as $key) {
  			$results[$key] = $this->singleGet($key);
  		}
  		return $results;
  	}

	/**
	 * Store an item.
	 *
	 * @param string $key The key under which to store the value.
	 * @param mixed $value The value to store.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function set($key, $value, $expiration=NULL){
		$mappedKey = $this->mapKey($key);
		$this->contents[$mappedKey] = $this->mapValue($value);
		$this->expirations[$mappedKey] = $expiration;
		return TRUE;
	}

	/**
	 * Increment a numeric item's value by the specified offset.
	 * If the item's value is not numeric, an error will result.
	 *
	 * @param string $key The key under which to store the value.
	 * @param int $offset The amount by which to increment the item's value.
	 * @param int $initialValue The value to set the item to if it doesn't currently exist.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function increment($key, $offset=1, $initialValue=0, $expiry=NULL){
		$currentValue = $this->get($key);
		if (NULL === $currentValue) {
			return $this->set($key, $initialValue, $expiry);
		}
		if (!is_numeric($currentValue)) {
			return FALSE;
		}
		return $this->set($key, $currentValue+$offset, $expiry);
	}

	/**
	 * Decrements a numeric item's value by the specified offset.
	 * If the item's value is not numeric, an error will result.
	 * If the operation would decrease the value below 0, the new value will be 0.
	 *
	 * @param string $key The key under which to store the value.
	 * @param int $offset The amount by which to increment the item's value.
	 * @param int $initialValue The value to set the item to if it doesn't currently exist.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function decrement($key, $offset=1, $initialValue=0, $expiry=NULL){
		$currentValue = $this->get($key);
		if (NULL === $currentValue) {
			return $this->set($key, $initialValue, $expiry);
		}
		if (!is_numeric($currentValue)) {
			return FALSE;
		}
		return $this->set($key, max($currentValue-$offset, 0), $expiry);
	}

}
