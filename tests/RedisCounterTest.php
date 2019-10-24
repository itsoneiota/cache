<?php
namespace itsoneiota\cache;
use PHPUnit\Framework\TestCase;
use \Predis\Client;
/**
 * Tests for Cache.
 *
 **/
class RedisCounterTest extends TestCase {

	protected $sut;
	protected $client;

	public function setUp() {
		$this->client = new Client();
		$this->client->flushall();
		$this->sut = new RedisCounter($this->client);
	}

	protected function assertKeyExists($key){
		$this->assertEquals(1, $this->client->exists($key), "Key '$key' does not exist, should.");
	}

	protected function assertKeyNotExists($key){
		$this->assertEquals(0, $this->client->exists($key), "Key '$key' exists, shouldn't..");
	}

	protected function assertTTL($key, $expected){
		$this->assertEquals($expected, $this->client->ttl($key));
	}

	protected function assertIntValue($exp, $act){
		$this->assertTrue(is_int($act), sprintf("Type mismatch. Expected int, got %s.", gettype($act)));
		$this->assertSame($exp, $act);
	}

	/**
	 * It should increment.
	 * @test
	 */
	public function canIncrement() {
		// Doesn't exist yet.
		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertIntValue(1, $this->sut->get('myKey'));

		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertIntValue(2, $this->sut->get('myKey'));
	}

	/**
	 * It should increment.
	 * @test
	 */
	public function canIncrementWithNonZeroInitialValue() {
		// Doesn't exist yet.
		$this->assertTrue($this->sut->increment('myKey', 3, 5));
		$this->assertEquals(8, $this->sut->get('myKey'));

		// Initial value should only be used if the key doesn't exist.
		$this->assertTrue($this->sut->increment('myKey', 2, 999));
		$this->assertIntValue(10, $this->sut->get('myKey'));
	}

	/**
	 * It should increment using key prefixes.
	 * @test
	 */
	public function canIncrementWithPrefix() {
		$this->sut = new RedisCounter($this->client, 'MYPREFIX', 666);
		// Doesn't exist yet.
		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertIntValue(1, $this->sut->get('myKey'));

		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertIntValue(2, $this->sut->get('myKey'));
	}

	/**
	 * It should increment with TTLs.
	 * @test
	 */
	public function canIncrementWithTTL() {
		$this->sut = new RedisCounter($this->client, NULL, 666);
		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertIntValue(1, $this->sut->get('myKey'));
		$this->assertTTL('myKey', 666);

		$this->assertTrue($this->sut->increment('myKey', 1, 0, 333));
		$this->assertIntValue(2, $this->sut->get('myKey'));
		$this->assertTTL('myKey', 333);
	}

	/**
	 * It should decrement.
	 * @test
	 */
	public function canDecrement() {
		$this->sut->decrement('myKey', 1, 100, 666);
		$this->assertIntValue(99, $this->sut->get('myKey'));
		$this->assertTTL('myKey', 666);
	}

}
