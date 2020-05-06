<?php
namespace itsoneiota\cache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Cache.
 *
 **/

class InMemoryCounterTest extends TestCase {
	protected $sut;
	protected $cache;

	public function setUp() {
		$this->sut = new InMemoryCounter();
	}

	/**
	 * It should increment/decrement.
	 * @test
	 */
	public function canCache() {
		// Increment
		$this->assertTrue($this->sut->increment('initialisedByIncrementing', 1, 5));
		$this->assertEquals(5, $this->sut->get('initialisedByIncrementing'));
		$this->assertTrue($this->sut->increment('initialisedByIncrementing', 1, 5));
		$this->assertEquals(6, $this->sut->get('initialisedByIncrementing'));

		// Decrement
		$this->assertTrue($this->sut->decrement('initialisedByDecrementing', 1, 5));
		$this->assertEquals(5, $this->sut->get('initialisedByDecrementing'));
		$this->assertTrue($this->sut->decrement('initialisedByDecrementing', 1, 5));
		$this->assertEquals(4, $this->sut->get('initialisedByDecrementing'));

		$this->sut->set('nonNumeric', 'A');
		$this->assertFalse($this->sut->increment('nonNumeric'));
		$this->assertFalse($this->sut->decrement('nonNumeric'));
	}

}
