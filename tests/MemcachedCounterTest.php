<?php
namespace itsoneiota\cache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Memcached.
 *
 **/
class MemcachedCounterTest extends TestCase {

	protected $sut;
    /** @var  Memcached*/
    protected $cache;

	public function setUp() {
		$this->cache = $this->getMockBuilder('\Memcached')->disableOriginalConstructor()->getMock();
		$this->sut = new MemcachedCounter($this->cache);
	}

	/**
	 * It should increment.
	 * @test
	 */
	public function canIncrement() {
		$this->cache->expects($this->once())->method('increment')->with($this->equalTo('myKey'))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->increment('myKey'));
	}

	/**
	 * It should add a key before incrementing for the first time, to get around a PHP Memcached bug.
	 *
	 * @see http://stackoverflow.com/questions/33550880/php-memcached-with-binary-protocol-junk-data-returned-after-increment
	 * @test
	 */
	 public function canAddBeforeIncrementBecausePHPMemcachedIsBroken() {
		 $initial = 3;
		 $offset = 2;

		 // It needs to add the key, but only once.
		 $this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'), $this->equalTo($initial))->will($this->returnValue(TRUE));

		 // It shouldn't increment the first time, so there should only be one.
		 $this->cache->expects($this->once())->method('increment')->with($this->equalTo('myKey'), $this->equalTo($offset))->will($this->returnValue(TRUE));

		 $this->assertTrue($this->sut->increment('myKey',$offset,$initial));
		 $this->assertTrue($this->sut->increment('myKey',$offset,$initial));
	 }

	/**
	 * It should decrement.
	 * @test
	 */
	public function canDecrement() {
		$this->cache->expects($this->once())->method('decrement')->with($this->equalTo('myKey'))->will($this->returnValue(TRUE));
		$this->assertTrue($this->sut->decrement('myKey'));
	}

	/**
	 * It should add a key before incrementing for the first time, to get around a PHP Memcached bug.
	 *
	 * @see http://stackoverflow.com/questions/33550880/php-memcached-with-binary-protocol-junk-data-returned-after-increment
	 * @test
	 */
	 public function canAddBeforeDecrementBecausePHPMemcachedIsBroken() {
		 $initial = 3;
		 $offset = 2;

		 // It needs to add the key, but only once.
		 $this->cache->expects($this->once())->method('add')->with($this->equalTo('myKey'), $this->equalTo($initial))->will($this->returnValue(TRUE));

		 // It shouldn't increment the first time, so there should only be one.
		 $this->cache->expects($this->once())->method('decrement')->with($this->equalTo('myKey'), $this->equalTo($offset))->will($this->returnValue(TRUE));

		 $this->assertTrue($this->sut->decrement('myKey',$offset,$initial));
		 $this->assertTrue($this->sut->decrement('myKey',$offset,$initial));
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
		$this->sut = new MemcachedCounter($this->cache, 'MYPREFIX');
		$this->cache->expects($this->once())->method('getMulti')->with($this->equalTo(['MYPREFIX.a','MYPREFIX.b']))->will($this->returnValue(['MYPREFIX.a'=>TRUE,'MYPREFIX.b'=>FALSE]));
		$result = $this->sut->get(['a','b']);
		$this->assertTrue($result['a']);
		$this->assertFalse($result['b']);
	}

}
