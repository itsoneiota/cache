<?php
namespace itsoneiota\cache;

/**
 * Limited interface to Memcached.
 */
class Redis {

	protected $cache;
	protected $defaultExpiration;
	protected $keyPrefix;

	/**
	 * Constructor.
	 *
	 * @param \Predis\Client $cache Cache instance.
	 * @param string $keyPrefix A prefix used before every cache key.
	 * @param int $defaultExpiration Default expiration time, in seconds. This can be overridden when adding/setting individual items.
	 */
	public function __construct(\Predis\ClientInterface $cache, $keyPrefix=NULL, $defaultExpiration=NULL ){
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
		return $this->setKey(
			$this->mapKey($key),
			$this->mapValue($value),
			$this->mapExpiration($expiration),
			'NX'
		);
	}

	protected function setKey($k, $v, $x=NULL, $option=NULL){
		$method = 'set';
		$args = [$k, $v];

		// Basics
		if($x){
			$args[] = 'ex';
			$args[] = $x;
		}
		if($option){
			if($x){
				$args[] = $option;
			}elseif($option == 'NX'){
				// I haven't found a null value for expiry, 
				// so we have to switch to setnx here.
				$method = 'setnx';
			}
		}
		$resp = call_user_func_array([$this->cache, $method], $args);
		$result = (is_int($resp) && $resp==1) || (string)$resp == 'OK';

		return $result;
	}

	/**
	 * Delete an item.
	 *
	 * @param string $key
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function delete($key) {
		return 1==$this->cache->del($this->mapKey($key));
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function flush() {
		return $this->cache->flushall();
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
		$mappedKeys = [];
		foreach ($keys as $i => $key) {
			$mappedKeys[$i] = $this->mapKey($key);
		}

		$values = $this->cache->mget($mappedKeys);
		if($values === FALSE) {
			return NULL;
		}

		$returnValues = [];
		for($i=0; $i<count($keys);$i++){
			$originalKey = $keys[$i];
			$returnValues[$originalKey] = $this->unmapValue($values[$i]);
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
		// TODO: This would be better using SET with the XX option,
		// but I don't think it's possible to use SET without expiry.
		if(!$this->cache->exists($this->mapKey($key))){
			return FALSE;
		}
		return $this->setKey(
			$this->mapKey($key),
			$this->mapValue($value),
			$this->mapExpiration($expiration)
		);
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
		return $this->setKey(
			$this->mapKey($key),
			$this->mapValue($value),
			$this->mapExpiration($expiration)
		);
	}

	/**
	 * Increment a numeric item's value by the specified offset.
	 * If the item's value is not numeric, an error will result.
	 * NB: a non-zero initial value or non-null expiration
	 * will cause extra calls to be made.
	 *
	 * @param string $key The key under which to store the value.
	 * @param int $offset The amount by which to increment the item's value.
	 * @param int $initialValue The value to set the item to if it doesn't currently exist.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function increment($key, $offset=1, $initialValue=0, $expiry=NULL){
		return $this->offset($key, $offset, $initialValue, $expiry);
	}

	// Generic increment/decrement.
	protected function offset($key, $offset, $initialValue, $expiry){
		$method = $offset < 0 ? 'decrby' : 'incrby';
		$offset = abs($offset);
		$k = $this->mapKey($key);
		$x = $this->mapExpiration($expiry);

		// TODO: Make setting the initial value more efficient.
		if($initialValue != 0){
			$this->add($k, $initialValue, $expiry);
		}
		// Return value is new value of key, so not useful for func return.
		$this->cache->$method($k, $offset);

		// TODO: Make setting the expiry more efficient.
		if($x){
			$this->cache->expire($k, $x);
		}
		return TRUE;
	}

	/**
	 * Decrements a numeric item's value by the specified offset.
	 * If the item's value is not numeric, an error will result.
	 * If the operation would decrease the value below 0, the new value will be 0.
	 * NB: a non-zero initial value or non-null expiration
	 * will cause extra calls to be made.
	 *
	 * @param string $key The key under which to store the value.
	 * @param int $offset The amount by which to increment the item's value.
	 * @param int $initialValue The value to set the item to if it doesn't currently exist.
	 * @param int $expiration The expiration time.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function decrement($key, $offset=1, $initialValue=0, $expiry=NULL){
		return $this->offset($key, -1 * $offset, $initialValue, $expiry);
	}

	/**
	 * get the result code from the last cache interaction
	 *
	 * @return int
	 */
	public function getResultCode(){
	    return NULL;
	}

	/**
	 * get the result message from the last cache response
	 *
	 * @return string
	 */
	public function getResultMessage(){
	    return NULL;
	}
}
