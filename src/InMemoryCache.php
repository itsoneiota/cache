<?php
namespace itsoneiota\cache;
/**
 * Wrapper for a simple array.
 */
class InMemoryCache extends CacheWrapper implements Cache {

	protected $contents = array();

	/**
	 * Constructor.
	 *
	 */
	public function __construct(){
		// No-op.
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
			return FALSE;
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
}
