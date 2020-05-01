<?php

namespace itsoneiota\cache;

/**
 * Limited interface to APCu
 * @package itsoneiota\cache
 */
class APCu extends CacheWrapper implements Cache
{
    /**@
     * APCuCounter constructor.
     *
     * @param string $keyPrefix
     */
    public function __construct($keyPrefix = null)
    {
        $this->setKeyPrefix($keyPrefix);
    }

    /**
     * Store the variable using this name. Keys are cache-unique,
     * so attempting to use apcu_add() to store data with a key that already exists
     * will not overwrite the existing data, and will instead return FALSE.
     * Returns TRUE if something has effectively been added into the cache, FALSE otherwise.
     *
     * @param string $key
     * @param mixed  $value
     * @param null   $expiration
     *
     * @return bool
     */
    public function add($key, $value, $expiration = null)
    {
        return apcu_add($this->mapKey($key), $this->mapValue($value), $expiration);
    }

    /**
     * Delete an item
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return apcu_delete($this->mapKey($key));
    }

    /**
     * Clears the APCu cache
     *
     * @return true always
     */
    public function flush()
    {
        return apcu_clear_cache();
    }

    /**
     * Retrieve one or more items.
     *
     * @param mixed $key The key of the item to retrieve, or an array of keys.
     * @return array|mixed|null Returns the value stored in the cache or null otherwise.
     */
    public function get($key)
    {
        if (is_array($key)) {
            return $this->multiGet($key);
        }
        $value = $this->unmapValue(apcu_fetch($this->mapKey($key)));
        return $value === false ? null : $value;
    }
    protected function multiGet(array $keys)
    {
        $keyMap = [];
        foreach ($keys as $key) {
            $mappedKey = $this->mapKey($key);
            $keyMap[$mappedKey] = $key;
        }
        $keysToLookUp = array_keys($keyMap);

        $values = $this->cache->apcu_fetch($keysToLookUp);
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
     * @param string $key
     * @param mixed  $value
     * @param null   $expiration
     *
     * @return bool
     */
    public function replace($key, $value, $expiration = null)
    {
        return apcu_store($this->mapKey($key), $this->mapValue($value), $expiration);
    }

    /**
     * Stores an item, replacing it if it already exists.
     *
     * @param string $key
     * @param mixed  $value
     * @param null   $expiration
     *
     * @return array|bool
     */
    public function set($key, $value, $expiration = null)
    {
        return apcu_store($this->mapKey($key), $this->mapValue($key), $expiration);
    }
}
