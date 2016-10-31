<?php
namespace itsoneiota\cache;

/**
 * Limited interface to Memcached.
 */
class RedisCounter extends CacheWrapper {

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

	protected function unmapValue($v){
		return (int)$v;
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

	// Generic increment/decrement.
	protected function offset($key, $offset, $initialValue, $expiry){
		$k = $this->mapKey($key);
		$x = $this->mapExpiration($expiry);

		$responses = $this->client->pipeline(function ($pipe) use($k,$offset,$initialValue,$x){
			if($offset != 0){
				$pipe->setnx($k,$initialValue);
			}
			$pipe->incrby($k, $offset);
			if(NULL !== $x){
				$pipe->expire($k, $x);
			}
		});
		return TRUE;
	}

}
