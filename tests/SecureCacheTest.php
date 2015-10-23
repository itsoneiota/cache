<?php
namespace oneiota\cache;
/**
 * Tests for SecureCache.
 *
 **/
class SecureCacheTest extends \PHPUnit_Framework_TestCase {
	
	protected $sut;
	protected $cache;

	// 'myValue' encrypted = '1VtVXbjyRD29TAoZIw9q5Q=='

	public function setUp() {
		$this->encryptionKey = 'THISISAVERYLONGANDDIFFICULTTOGUESSENCRYPTIONKEY';
		$this->cache = $this->getMockBuilder('\Memcached')->disableOriginalConstructor()->getMock();
		$this->sut = new SecureCache($this->cache, $this->encryptionKey);
	}
	
	/**
	 * It should store and retrieve a KVP.
	 * @test
	 */
	public function canAdd() {
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'),$this->equalTo('1VtVXbjyRD29TAoZIw9q5Q=='),$this->equalTo(120))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->add('myKey','myValue'));
	}
	
	/**
	 * It should get a KVP.
	 * @test
	 */
	public function canGet() {
		$this->cache->expects($this->once())->method('get')->with($this->equalTo('myKey'))->will($this->returnValue('1VtVXbjyRD29TAoZIw9q5Q=='));
		$this->assertEquals('myValue', $this->sut->get('myKey'));
	}
	
	/**
	 * It should store and retrieve a KVP using the default expiration time.
	 * @test
	 */
	public function canAddWithDefaultExpiration() {
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'),$this->equalTo('1VtVXbjyRD29TAoZIw9q5Q=='),$this->equalTo(5))->will($this->returnValue(TRUE));
		$this->sut = new SecureCache($this->cache, $this->encryptionKey,5);
		$this->assertTrue($this->sut->add('myKey','myValue'));
	}
	
	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canAddWithExplicitExpiration() {
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'),$this->equalTo('1VtVXbjyRD29TAoZIw9q5Q=='),$this->equalTo(10))->will($this->returnValue(TRUE));
		$this->sut = new SecureCache($this->cache, $this->encryptionKey, 5);
		$this->assertTrue($this->sut->add('myKey','myValue',10));
	}
	
	/**
	 * It should replace a KVP.
	 * @test
	 */
	public function canReplace() {
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('myKey'),$this->equalTo('1VtVXbjyRD29TAoZIw9q5Q=='),$this->equalTo(120))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->replace('myKey','myValue'));
	}
	
	/**
	 * It should store and retrieve a KVP using the default expiration time.
	 * @test
	 */
	public function canReplaceWithDefaultExpiration() {
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('myKey'),$this->equalTo('1VtVXbjyRD29TAoZIw9q5Q=='),$this->equalTo(5))->will($this->returnValue(TRUE));
		$this->sut = new SecureCache($this->cache, $this->encryptionKey,5);
		$this->assertTrue($this->sut->replace('myKey','myValue'));
	}
	
	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canReplaceWithExplicitExpiration() {
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('myKey'),$this->equalTo('1VtVXbjyRD29TAoZIw9q5Q=='),$this->equalTo(10))->will($this->returnValue(TRUE));
		$this->sut = new SecureCache($this->cache, $this->encryptionKey,5);
		$this->assertTrue($this->sut->replace('myKey','myValue',10));
	}
	
	/**
	 * It should delete a KVP.
	 * @test
	 */
	public function canDelete() {
		$this->cache->expects($this->once())->method('delete')->with($this->equalTo('myKey'))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->delete('myKey'));
	}
	
	/**
	 * It should flush the cache.
	 * @test
	 */
	public function canFlush() {
		$this->cache->expects($this->once())->method('flush')->will($this->returnValue(TRUE));
		$this->sut->flush();
	}
	
}