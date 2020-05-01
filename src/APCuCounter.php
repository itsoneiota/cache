<?php

namespace itsoneiota\cache;

/**
 * Limited interface to APCu
 * @package itsoneiota\cache
 */
class APCuCounter extends CacheWrapper implements Counter
{
    /**
     * APCuCounter constructor.
     *
     * @param string $keyPrefix
     */
    public function __construct($keyPrefix = null)
    {
        $this->setKeyPrefix($keyPrefix);
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
     * Increment a numeric item's value by the specified offset.
     * If the item's value is not numeric, an error will result.
     * If the item does not exist, it will be created with a value of
     * $initialValue and incremented by $offset.
     *
     * @param string $key The key under which to store the value.
     * @param int $offset The amount by which to increment the item's value.
     * @param int $initialValue The value to set the item to if it doesn't currently exist.
     * @param int $expiration The expiration time.
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function increment($key, $offset=1, $initialValue=0, $expiry=null)
    {
        $mappedKey = $this->mapKey($key);
        if (!apcu_exists($mappedKey)) {
            apcu_add($mappedKey, $initialValue);
        }
        return apcu_inc($mappedKey, $offset);
    }

    /**
     * Decrements a numeric item's value by the specified offset.
     * If the item's value is not numeric, an error will result.
     * If the item does not exist, it will be created with a value of
     * $initialValue and decremented by $offset.
     * If the operation would decrease the value below 0, the new value will be 0.
     *
     * @param string $key The key under which to store the value.
     * @param int $offset The amount by which to increment the item's value.
     * @param int $initialValue The value to set the item to if it doesn't currently exist.
     * @param int $expiration The expiration time.
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function decrement($key, $offset=1, $initialValue=0, $expiry=null)
    {
        $mappedKey = $this->mapKey($key);
        if (!apcu_exists($mappedKey)) {
            apcu_add($mappedKey, $initialValue);
        }
        $newValue = apcu_dec($mappedKey, $offset);
        return $newValue >= 0 ? $newValue : 0;
    }
}
