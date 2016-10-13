<?php
namespace itsoneiota\cache;
use \Predis\Client;
/**
 * Tests for Cache.
 *
 **/
class RedisTest extends \PHPUnit_Framework_TestCase {

	protected $sut;
	protected $client;

	public function setUp() {
		$this->client = new Client();
		$this->client->set('a','b','ex',100,'NX');
		$this->client->flushall();
		$this->sut = new Redis($this->client);
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

	/**
	 * It should add a KVP.
	 * @test
	 */
	public function canAdd() {
		$this->assertTrue($this->sut->add('myKey','myValue'));
		$this->assertEquals('myValue', $this->sut->get('myKey'));
		$this->assertTTL('myKey',-1);
	}

	/**
	 * It should add a KVP.
	 * @test
	 */
	public function canRejectAddIfExists() {
		$this->assertTrue($this->sut->add('myKey','myValue'));
		$this->assertFalse($this->sut->add('myKey','myValue'));
	}

	/**
	 * It should add a KVP.
	 * @test
	 */
	public function canAddWithKeyPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->assertTrue($this->sut->add('myKey','myValue'));
		$this->assertEquals('myValue', $this->sut->get('myKey'));
		$this->assertEquals('myValue', $this->client->get('MYPREFIX.myKey'));
	}

	/**
	 * It should allow the key prefix to be changed.
	 * @test
	 */
	public function canSetKeyPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->assertEquals('MYPREFIX.', $this->sut->getKeyPrefix());

		$this->sut->setKeyPrefix('NEWPREFIX');
		$this->assertEquals('NEWPREFIX.', $this->sut->getKeyPrefix());

		$this->assertTrue($this->sut->add('myKey','myValue'));
		$this->assertEquals('myValue', $this->client->get('NEWPREFIX.myKey'));
	}

	/**
	 * It should get a KVP.
	 * @test
	 */
	public function canGet() {
		$this->client->set('myKey','myValue');
		$this->assertEquals('myValue', $this->sut->get('myKey'));
	}

	/**
	 * It should get a KVP.
	 * @test
	 */
	public function canGetWithKeyPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->client->set('MYPREFIX.myKey', 'myValue');
		$this->assertEquals('myValue', $this->sut->get('myKey'));
	}

	/**
	 * It should add a KVP using the default expiration time.
	 * @test
	 */
	public function canAddWithDefaultExpiration() {
		$this->sut = new Redis($this->client,NULL,5);
		$this->assertTrue($this->sut->add('myKey','myValue'));
		$this->assertTTL('myKey',5);
	}

	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canAddWithExplicitExpiration() {
		$this->sut = new Redis($this->client);
		$this->assertTrue($this->sut->add('myKey','myValue',10));
		$this->assertTTL('myKey',10);

		$this->sut = new Redis($this->client, NULL, 3);
		$this->assertTrue($this->sut->add('anotherKey','myValue',5));
		$this->assertTTL('anotherKey',5);
	}

	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canSetWithExplicitExpiration() {
		$this->sut = new Redis($this->client);
		$this->assertTrue($this->sut->set('myKey','myValue',10));
		$this->assertTTL('myKey',10);
	}

	/**
	 * It should replace a KVP.
	 * @test
	 */
	public function canReplace() {
		$this->assertFalse($this->sut->replace('myKey','myValue'));// Doesn't exist yet.
		$this->assertTrue($this->sut->set('myKey','myValue'));
		$this->assertTrue($this->sut->replace('myKey','newValue'));
		$this->assertEquals('newValue', $this->sut->get('myKey'));
		$this->assertEquals('newValue',$this->client->get('myKey'));
	}

	/**
	 * It should replace a KVP.
	 * @test
	 */
	public function canReplaceWithKeyPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->assertTrue($this->sut->set('myKey','myValue'));
		$this->assertTrue($this->sut->replace('myKey','newValue'));
		$this->assertEquals('newValue',$this->client->get('MYPREFIX.myKey'));
	}

	/**
	 * It should add a KVP using the default expiration time.
	 * @test
	 */
	public function canReplaceWithDefaultExpiration() {
		$this->sut = new Redis($this->client,NULL,5);
		$this->client->set('myKey', 'origValue', 'ex', 100);
		$this->assertTrue($this->sut->replace('myKey','myValue'));
		$this->assertTTL('myKey', 5);
	}

	/**
	 * It should store an retrieve a KVP using an explicit expiration time.
	 * @test
	 */
	public function canReplaceWithExplicitExpiration() {
		$this->sut = new Redis($this->client,NULL,5);
		$this->client->set('myKey', 'origValue', 'ex', 100);
		$this->assertTrue($this->sut->replace('myKey','myValue', 45));
		$this->assertEquals('myValue', $this->client->get('myKey'));
		$this->assertTTL('myKey', 45);
	}

	/**
	 * It should delete a KVP.
	 * @test
	 */
	public function canDelete() {
		$this->client->set('myKey', 'myValue');
		$this->client->set('otherKey', 'myValue');
		$this->assertKeyExists('myKey');
		$this->assertTrue($this->sut->delete('myKey'));
		$this->assertKeyNotExists('myKey');
		$this->assertKeyExists('otherKey');
	}

	/**
	 * It should flush the cache.
	 * @test
	 */
	public function canFlush() {
		$this->client->set('a','1');
		$this->client->set('b','2');
		$this->client->set('c','3');
		$this->assertKeyExists('a');
		$this->assertKeyExists('b');
		$this->assertKeyExists('c');
		
		$this->sut->flush();

		$this->assertKeyNotExists('a');
		$this->assertKeyNotExists('b');
		$this->assertKeyNotExists('c');
	}

	/**
	 * It should increment.
	 * @test
	 */
	public function canIncrement() {
		// Doesn't exist yet.
		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertEquals(1, $this->client->get('myKey'));

		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertEquals(2, $this->client->get('myKey'));
	}

	/**
	 * It should increment with TTLs.
	 * @test
	 */
	public function canIncrementWithTTL() {
		$this->sut = new Redis($this->client, NULL, 666);
		$this->assertTrue($this->sut->increment('myKey'));
		$this->assertEquals(1, $this->client->get('myKey'));
		$this->assertTTL('myKey', 666);

		$this->assertTrue($this->sut->increment('myKey', 1, 0, 333));
		$this->assertEquals(2, $this->client->get('myKey'));
		$this->assertTTL('myKey', 333);
	}

	/**
	 * It should decrement.
	 * @test
	 */
	public function canDecrement() {
		$this->sut->decrement('myKey', 1, 100, 666);
		$this->assertEquals(99, $this->client->get('myKey'));
		$this->assertTTL('myKey', 666);
	}

	/**
	 * It should get multiple keys at once.
	 * @test
	 */
	public function canGetMulti() {
		$this->client->set('a', 'foo');
		$this->client->set('a', 'foo');
		$this->client->set('b', 'bar');
		$result = $this->sut->get(['a','b']);
		$this->assertEquals('foo', $result['a']);
		$this->assertEquals('bar', $result['b']);
	}

	/**
	 * It should get multiple keys at once.
	 * @test
	 */
	public function canGetMultiWithPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->client->set('MYPREFIX.a', 'foo');
		$this->client->set('MYPREFIX.b', 'bar');
		$result = $this->sut->get(['a','b']);
		$this->assertEquals('foo', $result['a']);
		$this->assertEquals('bar', $result['b']);
	}

}
