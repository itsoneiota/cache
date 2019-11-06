<?php
namespace itsoneiota\cache;
/**
 * Tests for Cache.
 *
 **/
class InMemoryCacheTest extends \PHPUnit_Framework_TestCase {

	protected $sut;
	protected $cache;

	public function setUp() {
		$this->sut = new InMemoryCache();
	}

	/**
	 * It should add a KVP.
	 * @test
	 */
	public function canCache() {
		// Add
		$this->assertTrue($this->sut->add('myKey','myValue'));
		$this->assertTrue($this->sut->add('myKey2','myValue2'));

		$this->assertEquals('myValue', $this->sut->get('myKey'));
		$this->assertEquals('myValue2', $this->sut->get('myKey2'));

		// Can't re-add
		$this->assertFalse($this->sut->add('myKey','myValue'));

		// Set existing value
		$this->assertTrue($this->sut->set('myKey', 'newValue'));

		$this->assertEquals('newValue', $this->sut->get('myKey'));
		$this->assertEquals('myValue2', $this->sut->get('myKey2'));

		// Set new value
		$this->assertTrue($this->sut->set('otherKey', 'otherValue'));
		$this->assertEquals('otherValue', $this->sut->get('otherKey'));

		// Multiget
		$results = $this->sut->get(['otherKey', 'myKey', 'nonexistent', 'myKey2']);
		$this->assertEquals('otherValue', $results['otherKey']);
		$this->assertEquals('newValue', $results['myKey']);
		$this->assertNull($results['nonexistent']);
		$this->assertEquals('myValue2', $results['myKey2']);

		// Replace
		$this->assertTrue($this->sut->replace('myKey2', 'newValue2'));

		$this->assertEquals('newValue', $this->sut->get('myKey'));
		$this->assertEquals('newValue2', $this->sut->get('myKey2'));

		// Can't replace non-existent value
		$this->assertFalse($this->sut->replace('nonExistentKey', 'value'));

		// Delete
		$this->assertTrue($this->sut->delete('myKey'));

		$this->assertNull($this->sut->get('myKey'));
		$this->assertEquals('newValue2', $this->sut->get('myKey2'));

		// Can't delete non-existent value
		$this->assertFalse($this->sut->delete('nonExistentKey'));

		$this->sut->flush();

		$this->assertNull($this->sut->get('myKey'));
		$this->assertNull($this->sut->get('myKey2'));
		$this->assertNull($this->sut->get('otherKey'));
	}

}
