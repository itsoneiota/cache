<?php
namespace itsoneiota\cache;

/**
 * Limited interface to Memcached.
 */
abstract class Cache {

	protected $defaultExpiration;
	protected $keyPrefix;

	public function setKeyPrefix($keyPrefix) {
		$this->keyPrefix = NULL === $keyPrefix ? NULL : $keyPrefix . '.';
	}

	public function getKeyPrefix() {
		return $this->keyPrefix;
	}

	/**
	 * Set the default expiration time.
	 *
	 * @param int $defaultExpiration Default expiration time, in seconds.
	 * @return void
	 */
	public function setDefaultExpiration($defaultExpiration) {
		$this->defaultExpiration = $defaultExpiration;
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
	 * Hook method to map a key, for example to add a prefix.
	 *
	 * @param string $key Key to map.
	 * @return string Mapped key.
	 */
	protected function mapKey($key) {
		return is_null($this->keyPrefix) ? $key : $this->keyPrefix.$key;
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
	public abstract function add($key, $value, $expiration = NULL);

	/**
	 * Delete an item.
	 *
	 * @param string $key
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public abstract function delete($key);

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public abstract function flush();

	/**
	 * Retrieve an item.
	 *
	 * @param mixed $key The key of the item to retrieve, or an array of keys.
	 * @return mixed Returns the value stored in the cache or FALSE otherwise.
	 */
	public abstract function get($key);

	/**
	 * Replace the item under an existing key.
	 *
	 * @param string $key The key under which to store the value.
	 * @param mixed $value The value to store.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public abstract function replace($key, $value, $expiration=NULL);

	/**
	 * Store an item.
	 *
	 * @param string $key The key under which to store the value.
	 * @param mixed $value The value to store.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public abstract function set($key, $value, $expiration=NULL);

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
	public abstract function increment($key, $offset=1, $initialValue=0, $expiry=NULL);

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
	public abstract function decrement($key, $offset=1, $initialValue=0, $expiry=NULL);

}
