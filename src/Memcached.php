<?php
namespace itsoneiota\cache;

/**
 * Limited interface to Memcached.
 */
class Memcached extends CacheWrapper implements Cache {

	protected $cache;

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
