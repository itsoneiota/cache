<?php

namespace itsoneiota\cache;

use RedisClient\RedisClient;

class RedisCluster extends CacheWrapper implements Cache {

    protected $client;

    /**
     * Constructor.
     *
     * @param RedisClient $client
     * @param string $keyPrefix A prefix used before every cache key.
     * @param int $defaultExpiration Default expiration time, in seconds. This can be overridden when adding/setting individual items.
     * @param null $keySuffix
     * @internal param \Predis\Client $cache Cache instance.
     */
    public function __construct(RedisClient $client, $keyPrefix = NULL, $defaultExpiration = NULL, $keySuffix = NULL) {
        $this->client = $client;
        $this->setDefaultExpiration($defaultExpiration);
        $this->setKeyPrefix($keyPrefix);
        $this->setKeySuffix($keySuffix);
    }

    // Redis doesn't understand our types, so serialise values as JSON.
    protected function mapValue($v) {
        return serialize($v);
    }

    protected function unmapValue($v) {
        return unserialize($v);
    }

    protected function setKey($k, $v, $x = NULL) {
        $resp = null;
        if(!empty($x)) {
            $resp = $this->client->set($k, $v, $x);
        }
        else {
            $resp = $this->client->set($k, $v);
        }
        return $resp;
    }

    /**
     * Store an item.
     *
     * @param string $key The key under which to store the value.
     * @param mixed $value The value to store.
     * @param int $expiration The expiration time.
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function set($key, $value, $expiration=NULL) {
        $this->updateMetric("redisCluster","set", $this->mapKey($key));
        return $this->setKey(
            $this->mapKey($key),
            $this->mapValue($value),
            $this->mapExpiration($expiration)
        );
    }

    /**
     * Add an item under a new key.
     *
     * @param string $key The key under which to store the value.
     * @param mixed $value The value to store.
     * @param int $expiration The expiration time.
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function add($key, $value, $expiration = NULL)
    {
        $this->updateMetric("redisCluster", "add", $this->mapKey($key));
        return $this->setKey(
            $this->mapKey($key),
            $this->mapValue($value),
            $this->mapExpiration($expiration)
        );
    }

    /**
     * Delete an item.
     *
     * @param string $key
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function delete($key) {
        $this->updateMetric("redisCluster","del", $this->mapKey($key));
        return 1==$this->client->del($this->mapKey($key));
    }

    /**
     * Retrieve an item.
     *
     * @param mixed $key The key of the item to retrieve, or an array of keys.
     * @return mixed Returns the value stored in the cache or FALSE otherwise.
     */
    public function get($key) {
        if (is_array($key)) {
            $this->updateMetric("redisCluster","mget", $this->mapKey($key));
            return $this->multiGet($key);
        }
        $this->updateMetric("redisCluster","get", $this->mapKey($key));
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
        $this->updateMetric("redisCluster","replace", $this->mapKey($key));
        return $this->setKey(
            $this->mapKey($key),
            $this->mapValue($value),
            $this->mapExpiration($expiration)
        );
    }

    /**
     * Invalidate all items in the cache.
     *
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function flush() {
        $this->updateMetric("redisCluster","flushAll", $this->mapKey($key));
        return $this->client->flushall();
    }


}
