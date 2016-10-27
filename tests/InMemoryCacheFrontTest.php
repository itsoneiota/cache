<?php
namespace itsoneiota\cache;
/**
 * Tests for InMemoryCacheFront.
 *
 **/
class InMemoryCacheFrontTest extends \PHPUnit_Framework_TestCase {

	protected $sut;

	public function setUp() {
		$this->cache = new MockCache();
		$this->sut = new InMemoryCacheFront($this->cache);
	}

	/**
	 * It should cache when reading.
	 * @test
	 */
	public function canReadThroughCache() {
		$this->cache->set('myKey', 'before');
		$this->assertEquals('before', $this->sut->get('myKey'));

		// We can be sure that the SUT has cached the value by changing it in the underlying cache.
		$this->cache->set('myKey', 'after');
		$this->assertEquals('before', $this->sut->get('myKey'), $this->sut->get('myKey'));
	}

	/**
	 * It should flush its cache and the underlying Cache.
	 * @test
	 */
	public function canFlush() {
		$this->cache->set('myKey', 'before');
		$this->assertEquals('before', $this->sut->get('myKey'));

		// We can be sure that the SUT has cached the value by changing it in the underlying cache.
		$this->cache->set('myKey', 'after');
		$this->assertEquals('before', $this->sut->get('myKey'), $this->sut->get('myKey'));

		$this->sut->flush();
		$this->assertNull($this->sut->get('myKey'));
		$this->assertNull($this->cache->get('myKey'));
	}

	/**
	 * It should limit the number of items in its locale cache.
	 * @test
	 */
	public function canLimitCacheFrontSize() {
		$maxSize = 3;
		$this->sut = new InMemoryCacheFront($this->cache, $maxSize);

		/**
		 * We will pull four values through the cache, then change them in the underlying cache.
		 * The first value retrieved should be fetched again from the underlying cache, while the
		 * others should retain their cached values.
		 */
		$this->cache->set('one', 'un');
		$this->cache->set('two', 'dau');
		$this->cache->set('three', 'tri');
		$this->cache->set('four', 'pedwar');

		$this->assertEquals('un', $this->sut->get('one'));
		$this->assertEquals('dau', $this->sut->get('two'));
		$this->assertEquals('tri', $this->sut->get('three'));
		$this->assertEquals('pedwar', $this->sut->get('four'));

		$this->cache->set('one', 'eins');
		$this->cache->set('two', 'zwei');
		$this->cache->set('three', 'drei');
		$this->cache->set('four', 'vier');

		// Ask in reverse order to make sure that new fetches don't flush out old values.
		$this->assertEquals('pedwar', $this->sut->get('four'));
		$this->assertEquals('tri', $this->sut->get('three'));
		$this->assertEquals('dau', $this->sut->get('two'));
		$this->assertEquals('eins', $this->sut->get('one')); // Only this one should have dropped.

	}

	/**
	 * It should write through the cache when adding.
	 * @test
	 */
	public function canAddThroughCache() {
		$this->sut->add('myKey', 'before');
		$this->assertEquals('before', $this->cache->get('myKey'));
		$this->assertEquals('before', $this->sut->get('myKey'));


		// We can be sure that the SUT has cached the value by changing it in the underlying cache.
		$this->cache->set('myKey', 'after');
		$this->assertEquals('before', $this->sut->get('myKey'), $this->sut->get('myKey'));
	}

	/**
	 * It should write through the cache when setting.
	 * @test
	 */
	public function canSetThroughCache() {
		$this->sut->set('myKey', 'before');
		$this->assertEquals('before', $this->sut->get('myKey'));
		$this->assertEquals('before', $this->cache->get('myKey'));

		// We can be sure that the SUT has cached the value by changing it in the underlying cache.
		$this->cache->set('myKey', 'after');
		$this->assertEquals('before', $this->sut->get('myKey'), $this->sut->get('myKey'));
	}

	/**
	 * It should replace through the cache.
	 * @test
	 */
	public function canReplace() {
		$this->sut->set('myKey', 'before');
		$this->assertEquals('before', $this->sut->get('myKey'));
		$this->assertEquals('before', $this->cache->get('myKey'));

		$this->cache->set('myKey', 'between');

		$this->sut->replace('myKey', 'after');
		$this->assertEquals('after', $this->sut->get('myKey'));
		$this->assertEquals('after', $this->cache->get('myKey'));
	}

	/**
	 * It should delete through the cache.
	 * @test
	 */
	public function canDelete() {
		$this->sut->set('myKey', 'before');
		$this->assertEquals('before', $this->sut->get('myKey'));
		$this->assertEquals('before', $this->cache->get('myKey'));

		$this->sut->delete('myKey');

		$this->assertNull($this->cache->get('myKey'));
		$this->assertNull($this->sut->get('myKey'));

		$this->cache->set('myKey', 'afterDelete');
		$this->assertEquals('afterDelete', $this->sut->get('myKey'));
	}

	/**
	 * It should
	 * @test
	 */
	public function canIncrementAndDecrement() {
		$this->cache->set('alreadyInitialised', 10);
		$this->assertTrue($this->sut->increment('alreadyInitialised', 1, 5));
		$this->assertEquals(11, $this->sut->get('alreadyInitialised'));
		$this->assertTrue($this->sut->increment('alreadyInitialised', 1, 5));
		$this->assertEquals(12, $this->sut->get('alreadyInitialised'));

		// Increment and initialise.
		$this->assertTrue($this->sut->increment('initialisedByIncrementing', 1, 5));
		$this->assertEquals(5, $this->sut->get('initialisedByIncrementing'));
		$this->assertTrue($this->sut->increment('initialisedByIncrementing', 1, 5));
		$this->assertEquals(6, $this->sut->get('initialisedByIncrementing'));

		// Decrement
		$this->cache->set('alreadyInitialised', 10);
		$this->assertTrue($this->sut->decrement('alreadyInitialised', 1, 5));
		$this->assertEquals(9, $this->sut->get('alreadyInitialised'));
		$this->assertTrue($this->sut->decrement('alreadyInitialised', 1, 5));
		$this->assertEquals(8, $this->sut->get('alreadyInitialised'));

		$this->assertTrue($this->sut->decrement('initialisedByDecrementing', 1, 5));
		$this->assertEquals(5, $this->sut->get('initialisedByDecrementing'));
		$this->assertTrue($this->sut->decrement('initialisedByDecrementing', 1, 5));
		$this->assertEquals(4, $this->sut->get('initialisedByDecrementing'));

		$this->sut->set('nonNumeric', 'A');
		$this->assertFalse($this->sut->increment('nonNumeric'));
		$this->assertFalse($this->sut->decrement('nonNumeric'));
	}

	/**
	 * It should get multiple keys.
	 * @test
	 */
	public function canMultiGet() {
		$cache = $this->getMockBuilder('\itsoneiota\cache\Cache')->disableOriginalConstructor()->getMock();
		$this->sut = new InMemoryCacheFront($cache);

		$cache->expects($this->at(0))->method('get')->with($this->equalTo(['a','c']))->will($this->returnValue(['a'=>'apple', 'c'=>'carrot']));
		$cache->expects($this->at(1))->method('get')->with($this->equalTo(['b','d']))->will($this->returnValue(['b'=>'biscuit', 'd'=>'donut']));

		$results = $this->sut->get(['a','c']);
		$this->assertEquals('apple', $results['a']);
		$this->assertEquals('carrot', $results['c']);

		$results = $this->sut->get(['a','b', 'c', 'd']);
		$this->assertEquals('apple', $results['a']);
		$this->assertEquals('biscuit', $results['b']);
		$this->assertEquals('carrot', $results['c']);
		$this->assertEquals('donut', $results['d']);
	}

}
