<?php
namespace itsoneiota\cache;
use \base\common\domain\Store;

/**
 * Limited interface to Memcached.
 */
class Cache {

	protected $cache;
	protected $defaultExpiration;
	protected $keyPrefix;

	/**
	 * Constructor.
	 *
	 * @param Memcached $cache Cache instance.
	 * @param string $keyPrefix A prefix used before every cache key.
	 * @param int $defaultExpiration Default expiration time, in seconds. This can be overridden when adding/setting individual items.
	 */
	public function __construct(\Memcached $cache, $keyPrefix=NULL, $defaultExpiration=0 ){
		$this->cache = $cache;
		$this->setDefaultExpiration($defaultExpiration);

		$this->setKeyPrefix($keyPrefix);
	}

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
	public function add($key, $value, $expiration = NULL){
		return $this->cache->add($this->mapKey($key),$this->mapValue($value),$this->mapExpiration($expiration));
	}

	/**
	 * Delete an item.
	 *
	 * @param string $key
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function delete($key) {
		return $this->cache->delete($this->mapKey($key));
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function flush() {
		return $this->cache->flush();
	}

	/**
	 * Retrieve an item.
	 *
	 * @param mixed $key The key of the item to retrieve, or an array of keys.
	 * @return mixed Returns the value stored in the cache or FALSE otherwise.
	 */
	public function get($key) {
		if (is_array($key)) {
			return $this->multiGet($key);
		}
		$value = $this->unmapValue($this->cache->get($this->mapKey($key)));
		return $value === FALSE ? NULL : $value;
	}

	protected function multiGet(array $keys){
		$keyMap = [];
		foreach ($keys as $key) {
			$mappedKey = $this->mapKey($key);
			$keyMap[$mappedKey] = $key;
		}
		$keysToLookUp = array_keys($keyMap);

		$values = $this->cache->getMulti($keysToLookUp);
		if($values === FALSE) {
			return NULL;
		}

		$returnValues = [];
		foreach ($values as $mappedKey => $value) {
			$originalKey = $keyMap[$mappedKey];
			$returnValues[$originalKey] = $this->unmapValue($value);
		}
		return $returnValues;
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
		return $this->cache->replace($this->mapKey($key), $this->mapValue($value), $this->mapExpiration($expiration));
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
		return $this->cache->set($this->mapKey($key), $this->mapValue($value), $this->mapExpiration($expiration));
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
		return $this->cache->increment($this->mapKey($key), $offset, $initialValue, $this->mapExpiration($expiry));
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
		return $this->cache->decrement($this->mapKey($key), $offset, $initialValue, $this->mapExpiration($expiry));
	}

	/**
	 * get the result code from the last cache interaction
	 *
	 * @return int
	 */
	public function getResultCode(){
	    return $this->cache->getResultCode();
	}

	/**
	 * get the result message from the last cache response
	 *
	 * @return string
	 */
	public function getResultMessage(){
	    return $this->cache->getResultMessage();
	}
}
