<?php
namespace itsoneiota\cache;

/**
 * Wrapper that provides a read- and write-through in-memory cache for a Cache instance.
 * It retains a fixed number of local entries.
 *
 * The assumption here is that, for the duration of its lifetime,
 * the underlying cache will only be accessed through this object.
 */
class InMemoryCacheFront extends CacheWrapper implements Cache {

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
		return array_key_exists($key, $this->contents) ? $this->contents[$key] : $this->doGet($key);
	}

	protected function doGet($key){
		$result = $this->cache->get($key);
		if (NULL !== $result) {
			$this->contents[$key] = $result;
			$this->checkLength();
		}
		return $result;
	}

	protected function multiGet(array $keys){
		$results = [];
		$keysToFind = [];
		foreach ($keys as $key) {
			if(array_key_exists($key, $this->contents)){
				 $results[$key] = $this->contents[$key];
			}else{
				$keysToFind[] = $key;
			}
		}
		if (empty($keysToFind)) {
			// Everything was found in local memory.
			// No need to checkLength.
			return $results;
		}
		$cacheResults = [];
		if (count($keysToFind) == 1) {
			$key = reset($keysToFind);
			$results[$key] = $this->singleGet($key);
		}else{
			$results = array_merge($results, $this->multiGetFromCache($keysToFind));
		}
		return $results;
	}

	protected function multiGetFromCache(array $keysToFind){
		$results = $this->cache->get($keysToFind);
		foreach ($results as $key => $value) {
			$this->contents[$key] = $value;
		}
		$this->checkLength();
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
}
