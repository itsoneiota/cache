<?php
namespace itsoneiota\cache;

/**
 * Limited interface to Memcached.
 */
class MemcachedCounter extends CacheWrapper implements Counter {

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

	protected $incrementDecrementKeys = [];
	/**
	 * Initialise a key for use with `increment()` and `decrement()`.
	 *
	 * @param string $key
	 * @param int $initialValue
	 */
	protected function initialiseIncrementDecrementKey($key, $initialValue, $expiry){
		if (!is_int($initialValue)) {
			throw new \InvalidArgumentException('Initial value must be an integer.');
		}
		$added = FALSE;
		if (!in_array($key, $this->incrementDecrementKeys)) {
			$added = $this->cache->add($this->mapKey($key),$this->mapValue($initialValue),$this->mapExpiration($expiry));
			$this->incrementDecrementKeys[] = $key;
		}
		return $added;
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
		/**
		 * Due to a bug in the PHP Memcached client, keys incremented
		 * with an initial value can return junk data in their results.
		 *
		 * The only real way around this is to ensure that the key exists,
		 * and set its initial value before incrementing.
		 *
		 * "Memcached::increment() will set the item to the initial_value parameter if the key doesn't exist."
		 * With this in mind, if we always tried to `add()` _and_ `increment()`,
		 * we'd end up with a value of initial + increment if the key didn't exist.
		 * So, we need to try adding the key, then only increment if it didn't previously exist.
		 *
		 * @see http://stackoverflow.com/questions/33550880/php-memcached-with-binary-protocol-junk-data-returned-after-increment
		 */
		$keyAdded = $this->initialiseIncrementDecrementKey($key, $initialValue, $expiry);
		if ($keyAdded) {
			return TRUE;
		}
		/**
		 * Unfortunately, we still need to set initialValue on increment, so that we can supply the expiry.
		 */
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
		/**
		 * Due to a bug in the PHP Memcached client, keys decremented
		 * with an initial value can return junk data in their results.
		 *
		 * The only real way around this is to ensure that the key exists,
		 * and set its initial value before decrementing.
		 *
		 * "Memcached::decrement() will set the item to the initial_value parameter if the key doesn't exist."
		 * With this in mind, if we always tried to `add()` _and_ `decrement()`,
		 * we'd end up with a value of initial - decrement if the key didn't exist.
		 * So, we need to try adding the key, then only decrement if it didn't previously exist.
		 *
		 * @see http://stackoverflow.com/questions/33550880/php-memcached-with-binary-protocol-junk-data-returned-after-increment
		 */
		$keyAdded = $this->initialiseIncrementDecrementKey($key, $initialValue, $expiry);
 		if ($keyAdded) {
 			return TRUE;
 		}
		/**
		 * Unfortunately, we still need to set initialValue on decrement, so that we can supply the expiry.
		 */
		return $this->cache->decrement($this->mapKey($key), $offset, $initialValue, $this->mapExpiration($expiry));
	}
}
