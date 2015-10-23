<?php
namespace oneiota\cache;
use \DateTime;
/**
 * Tests for CacheValue.
 *
 **/
class CacheValueTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * It should create a cache value with value and timestamp.
	 * @test
	 */
	public function canCreate() {
		$now = new DateTime();
		$sut = new CacheValue('foo', $now);

		$this->assertEquals('foo', $sut->value);
		$this->assertSame($now, $sut->timestamp);
	}

}