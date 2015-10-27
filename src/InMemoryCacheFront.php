<?php
namespace itsoneiota\cache;

/**
 * Wrapper that provides a read- and write-through in-memory cache for a Cache instance.
 * It retains a fixed number of local entries.
 *
 * The assumption here is that, for the duration of its lifetime,
 * the underlying cache will only be accessed through this object.
 */
class InMemoryCacheFront extends Cache {

	protected $maxSize;
	protected $contents;
	protected $cache;

	/**
	 * Constructor.
	 *
	 * @param Cache $cache The underlying cache.
	 * @param int $maxSize The maximum number of items to be retained.
	 */
	public function __construct(Cache $cache, $maxSize = 100) {
		$this->cache = $cache;
		$this->maxSize = $maxSize;
		$this->contents = array();
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
		$success = $this->cache->add($key, $value, $expiration);
		if ($success) {
			$this->contents[$key] = $value;
			$this->checkLength();
		}
		return $success;
	}

	protected function checkLength(){
		while(count($this->contents) > $this->maxSize) {
			array_shift($this->contents);
		}
	}

	/**
	 * Delete an item.
	 *
	 * @param string $key
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function delete($key) {
		$success = $this->cache->delete($key);
		if ($success) {
			unset($this->contents[$key]);
		}
		return $success;
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function flush() {
		$success = $this->cache->flush();
		if ($success) {
			$this->contents = array();
		}
	}

	/**
	 * Retrieve an item.
	 *
	 * @param mixed $key The key of the item to retrieve, or an array of keys.
	 * @return mixed Returns the value stored in the cache or NULL otherwise.
	 */
	public function get($key) {
		return is_array($key) ? $this->multiGet($key) : $this->singleGet($key);
	}

	protected function singleGet($key){
		$result = array_key_exists($key, $this->contents) ? $this->contents[$key] : $this->cache->get($key);
		if (NULL !== $result) {
			$this->contents[$key] = $result;
			$this->checkLength();
		}
		return $result;
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
		$success = $this->cache->replace($key, $value, $expiration);
		if ($success) {
			$this->contents[$key] = $value;
		}
		return $success;
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
		$success = $this->cache->set($key, $value, $expiration);
		if ($success) {
			$this->contents[$key] = $value;
			$this->checkLength();
		}
		return $success;
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
		$success = $this->cache->increment($this->mapKey($key), $offset, $initialValue, $this->mapExpiration($expiry));
		if ($success) {
			if (array_key_exists($key, $this->contents)) {
				$this->contents[$key] += $offset;
			}else{
				$this->contents[$key] = $initialValue;
			}
			$this->checkLength();
		}
		return $success;
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
		$success = $this->cache->decrement($this->mapKey($key), $offset, $initialValue, $this->mapExpiration($expiry));
		if ($success) {
			if (array_key_exists($key, $this->contents)) {
				$this->contents[$key] = max($this->contents[$key]-$offset, 0);
			}else{
				$this->contents[$key] = $initialValue;
			}
			$this->checkLength();
		}
		return $success;
	}
}
