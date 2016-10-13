<?php
namespace itsoneiota\cache;
/**
 * Mock cache.
 * @codeCoverageIgnore
 */
class MockCache extends \itsoneiota\cache\Cache {

	protected $contents = array();
	protected $expirations = array();
	protected $defaultExpiration;
	protected $keyPrefix;

	/**
	 * Constructor.
	 *
	 */
	public function __construct($keyPrefix=NULL, $defaultExpiration=0){
		$this->setDefaultExpiration($defaultExpiration);
		$this->setKeyPrefix($keyPrefix);
	}

	public function getContents() {
		return $this->contents;
	}

	public function getExpiration($key) {
		$mappedKey = $this->mapKey($key);
		return array_key_exists($mappedKey, $this->expirations) ? $this->expirations[$mappedKey] : NULL;
	}

	/**
	 * Simulate the passage of time.
	 *
	 * @param int $seconds Number of seconds to pass.
	 * @return void
	 */
	public function timePasses($seconds) {
		foreach($this->expirations as $key => $value) {
			if (is_int($value) && $value < $seconds) {
				$this->delete($key);
			}
		}
	}

	/**
	 * Map the expiration value. Use instance default if none.
	 *
	 * @param int $expiration
	 * @return int Mapped expiration.
	 */
	public function mapExpiration($expiration=NULL) {
		return is_null($expiration) ? $this->defaultExpiration : $expiration;
	}

	/**
	 * Hook method to map a value, for example to encrypt the value.
	 *
	 * @param mixed $value Value to map.
	 * @return mixed Mapped value.
	 */
	protected function mapValue($value) {
		return $value;
	}

	/**
	 * Hook method to convert a mapped value back to the original.
	 * i.e. $a == unmapValue(mapValue($a))
	 *
	 * @param string $value
	 * @return mixed Unmapped value.
	 */
	protected function unmapValue($value) {
		return $value;
	}

	/**
	 * Add an item under a new key.
	 *
	 * @param string $key The key under which to store the value.
	 * @param mixed $value The value to store.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function add($key, $value, $expiration = NULL){
		$mappedKey = $this->mapKey($key);
		if (array_key_exists($mappedKey, $this->contents)) {
			return FALSE;
		}

		$this->contents[$mappedKey] = $this->mapValue($value);
		$this->expirations[$mappedKey] = $expiration;
		return TRUE;
	}

	/**
	 * Delete an item.
	 *
	 * @param string $key
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function delete($key) {
		if (!array_key_exists($this->mapKey($key), $this->contents)&&!array_key_exists($key,$this->contents)) {
			return FALSE;
		}
		unset($this->contents[$this->mapKey($key)]);
		unset($this->contents[$key]);
		return TRUE;
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function flush() {
		$this->contents = array();
		return TRUE;
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
	 * Replace the item under an existing key.
	 *
	 * @param string $key The key under which to store the value.
	 * @param mixed $value The value to store.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function replace($key, $value, $expiration=NULL){
		if (!array_key_exists($this->mapKey($key), $this->contents)) {
			return NULL;
		}
		$this->contents[$this->mapKey($key)] = $this->mapValue($value);
		return TRUE;
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
