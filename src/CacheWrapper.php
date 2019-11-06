<?php
namespace itsoneiota\cache;

abstract class CacheWrapper {

	protected $defaultExpiration;
	protected $keyPrefix;
    protected $keySuffix;

	public function setKeyPrefix($keyPrefix) {
		$this->keyPrefix = NULL === $keyPrefix ? NULL : $keyPrefix . '.';
	}

	public function getKeyPrefix() {
		return $this->keyPrefix;
	}

    public function setKeySuffix($keySuffix) {
        $this->keySuffix = NULL === $keySuffix ? NULL : '.' . $keySuffix;
    }

    public function getKeySuffix() {
        return $this->keySuffix;
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
        $key = is_null($this->keyPrefix) ? $key : $this->keyPrefix.$key;
        $key = is_null($this->keySuffix) ? $key : $key.$this->keySuffix;
		return $key;
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
}
