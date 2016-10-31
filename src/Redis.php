<?php
namespace itsoneiota\cache;

/**
 * Limited interface to Memcached.
 */
class Redis extends CacheWrapper implements Cache {

	protected $client;

	/**
	 * Constructor.
	 *
	 * @param \Predis\Client $cache Cache instance.
	 * @param string $keyPrefix A prefix used before every cache key.
	 * @param int $defaultExpiration Default expiration time, in seconds. This can be overridden when adding/setting individual items.
	 */
	public function __construct(\Predis\ClientInterface $client, $keyPrefix=NULL, $defaultExpiration=NULL ){
		$this->client = $client;
		$this->setDefaultExpiration($defaultExpiration);

		$this->setKeyPrefix($keyPrefix);
	}

	// Redis doesn't understand our types, so serialise values as JSON.
	protected function mapValue($v){
		return serialize($v);
	}
	protected function unmapValue($v){
		return unserialize($v);
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
		$args = [$k, $v];

		// Basics
		if($x){
			$args[] = 'ex';
			$args[] = $x;
		}
		if($option){
			$args[] = $option;
		}
		$resp = call_user_func_array([$this->client, 'set'], $args);
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
		return 1==$this->client->del($this->mapKey($key));
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function flush() {
		return $this->client->flushall();
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
		$value = $this->unmapValue($this->client->get($this->mapKey($key)));
		return $value === FALSE ? NULL : $value;
	}

	protected function multiGet(array $keys){
		$mappedKeys = [];
		foreach ($keys as $i => $key) {
			$mappedKeys[$i] = $this->mapKey($key);
		}

		$values = $this->client->mget($mappedKeys);
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
		return $this->setKey(
			$this->mapKey($key),
			$this->mapValue($value),
			$this->mapExpiration($expiration),
			'XX'
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

	public function increment($key, $offset=1, $initialValue=0, $expiry=NULL){
		throw new \RuntimeException('Increment is not supported by this Redis client.');
	}

	public function decrement($key, $offset=1, $initialValue=0, $expiry=NULL){
		throw new \RuntimeException('Decrement is not supported by this Redis client.');
	}
}
