<?php
namespace oneiota\cache;
/**
 * Limited interface to Memcached.
 * All values are encrypted when stored.
 */
class SecureCache extends Cache {
	
	protected $cache;
	protected $defaultExpiration;
	protected $encryptionKey;
	
	/**
	 * Constructor.
	 *
	 * @param Memcached $cache Cache instance.
	 * @param string $encryptionKey Encryption key.
	 * @param int $defaultExpiration Default expiration time, in seconds.
	 */
	public function __construct(\Memcached $cache, $encryptionKey, $defaultExpiration=120){
		$this->cache = $cache;
		$this->encryptionKey = $encryptionKey;
		$this->setDefaultExpiration($defaultExpiration);
	}

	/**
	 * Encrypt the value.
	 *
	 * @param mixed $value Value to map.
	 * @return mixed Mapped value.
	 */
	protected function mapValue($value) {
		return openssl_encrypt($value, 'AES-256-ECB', $this->encryptionKey);
	}
	
	/**
	 * Decrypt the value.
	 *
	 * @param mixed $value Value to unmap.
	 * @return mixed Unmapped value.
	 */
	protected function unmapValue($value) {
		return openssl_decrypt($value, 'AES-256-ECB', $this->encryptionKey);
	}

}
