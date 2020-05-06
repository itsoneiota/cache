<?php
namespace itsoneiota\cache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Memcached.
 *
 **/
class MemcachedTest extends TestCase{
	protected $sut;
	protected $cache;

	public function setUp() {
		$this->cache = $this->getMockBuilder('\Memcached')->disableOriginalConstructor()->getMock();
		$this->sut = new Memcached($this->cache);
	}

	/**
	 * It should add a KVP.
	 * @test
	 */
	public function canAdd() {
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(0))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->add('myKey','myValue'));
	}

	/**
	 * It should add a KVP.
	 * @test
	 */
	public function canAddWithKeyPrefix() {
		$this->sut = new Memcached($this->cache, 'MYPREFIX');
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('MYPREFIX.myKey'),$this->equalTo('myValue'),$this->equalTo(0))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->add('myKey','myValue'));
	}

	/**
	 * It should allow the key prefix to be changed.
	 * @test
	 */
	public function canSetKeyPrefix() {
		$this->sut = new Memcached($this->cache, 'MYPREFIX');
		$this->assertEquals('MYPREFIX.', $this->sut->getKeyPrefix());

		$this->sut->setKeyPrefix('NEWPREFIX');
		$this->assertEquals('NEWPREFIX.', $this->sut->getKeyPrefix());

		$this->cache->expects($this->once())->method('add')->with($this->equalTo('NEWPREFIX.myKey'),$this->equalTo('myValue'),$this->equalTo(0))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->add('myKey','myValue'));
	}

	/**
	 * It should get a KVP.
	 * @test
	 */
	public function canGet() {
		$this->cache->expects($this->once())->method('get')->with($this->equalTo('myKey'))->will($this->returnValue('myValue'));
		$this->assertEquals('myValue', $this->sut->get('myKey'));
	}

	/**
	 * It should get a KVP.
	 * @test
	 */
	public function canGetWithKeyPrefix() {
		$this->sut = new Memcached($this->cache, 'MYPREFIX');
		$this->cache->expects($this->once())->method('get')->with($this->equalTo('MYPREFIX.myKey'))->will($this->returnValue('myValue'));
		$this->assertEquals('myValue', $this->sut->get('myKey'));
	}

	/**
	 * It should add a KVP using the default expiration time.
	 * @test
	 */
	public function canAddWithDefaultExpiration() {
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(5))->will($this->returnValue(TRUE));
		$this->sut = new Memcached($this->cache,NULL,5);
		$this->assertTrue($this->sut->add('myKey','myValue'));
	}

	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canAddWithExplicitExpiration() {
		$this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(10))->will($this->returnValue(TRUE));
		$this->sut = new Memcached($this->cache);
		$this->assertTrue($this->sut->add('myKey','myValue',10));
	}

	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canSetWithExplicitExpiration() {
		$this->cache->expects($this->once())->method('set')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(10))->will($this->returnValue(TRUE));
		$this->sut = new Memcached($this->cache);
		$this->assertTrue($this->sut->set('myKey','myValue',10));
	}

	/**
	 * It should replace a KVP.
	 * @test
	 */
	public function canReplace() {
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(0))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->replace('myKey','myValue'));
	}

	/**
	 * It should replace a KVP.
	 * @test
	 */
	public function canReplaceWithKeyPrefix() {
		$this->sut = new Memcached($this->cache, 'MYPREFIX');
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('MYPREFIX.myKey'),$this->equalTo('myValue'),$this->equalTo(0))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->replace('myKey','myValue'));
	}

	/**
	 * It should add a KVP using the default expiration time.
	 * @test
	 */
	public function canReplaceWithDefaultExpiration() {
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(5))->will($this->returnValue(TRUE));
		$this->sut = new Memcached($this->cache,NULL,5);
		$this->assertTrue($this->sut->replace('myKey','myValue'));
	}

	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canReplaceWithExplicitExpiration() {
		$this->cache->expects($this->once())->method('replace')->with($this->equalTo('myKey'),$this->equalTo('myValue'),$this->equalTo(10))->will($this->returnValue(TRUE));
		$this->sut = new Memcached($this->cache);
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

	/**
	 * It should get the result code and message from the Memcached client.
	 * @test
	 */
	public function canGetResultCodeAndMessage() {
		$this->cache->expects($this->atLeastOnce())->method('getResultCode')->will($this->returnValue(123));
		$this->cache->expects($this->atLeastOnce())->method('getResultMessage')->will($this->returnValue('ALARM ALARM!'));

		$this->assertEquals(123, $this->sut->getResultCode());
		$this->assertEquals('ALARM ALARM!', $this->sut->getResultMessage());
	}

	/**
	 * It should get multiple keys at once.
	 * @test
	 */
	public function canGetMulti() {
		$this->cache->expects($this->once())->method('getMulti')->with($this->equalTo(['a','b']))->will($this->returnValue(['a'=>TRUE,'b'=>FALSE]));
		$result = $this->sut->get(['a','b']);
		$this->assertTrue($result['a']);
		$this->assertFalse($result['b']);
	}

	/**
	 * It should get multiple keys at once.
	 * @test
	 */
	public function canGetMultiWithPrefix() {
		$this->sut = new Memcached($this->cache, 'MYPREFIX');
		$this->cache->expects($this->once())->method('getMulti')->with($this->equalTo(['MYPREFIX.a','MYPREFIX.b']))->will($this->returnValue(['MYPREFIX.a'=>TRUE,'MYPREFIX.b'=>FALSE]));
		$result = $this->sut->get(['a','b']);
		$this->assertTrue($result['a']);
		$this->assertFalse($result['b']);
	}

}
