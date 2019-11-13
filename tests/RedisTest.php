<?php
namespace itsoneiota\cache;
use PHPUnit\Framework\TestCase;
use \Predis\Client;
/**
 * Tests for Cache.
 *
 **/
class RedisTest extends TestCase {

    /** @var  Redis*/
	protected $sut;

	protected $client;

	public function setUp() {
		$this->client = new Client();
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
     * CAN DO METIRCS
     * @test
     */
    public function canDoMetrics()
    {
        $this->sut =\Mockery::mock(Redis::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->sut->setClient($this->client);
        $this->sut->shouldReceive("getLogMetrics")->twice()->andReturnTrue();
        $this->sut->shouldReceive('updateMetric')->once()->withArgs(["add", "myKey"]);
        $this->sut->shouldReceive('updateMetric')->once()->withArgs(["get", "myKey"]);
        $this->assertTrue($this->sut->add('myKey', 'myValue'));
        $this->assertEquals('myValue', $this->sut->get('myKey'));
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

		$other = new Redis($this->client, 'OTHERPREFIX');
		$this->assertTrue($other->add('myKey','otherValue'));

		$this->assertEquals('myValue', $this->sut->get('myKey'));
		$this->assertEquals('otherValue', $other->get('myKey'));
	}

	/**
	 * It should allow the key prefix to be changed.
	 * @test
	 */
	public function canSetKeyPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->assertTrue($this->sut->add('myKey','myValue'));

		$this->sut->setKeyPrefix('OTHERPREFIX');
		$this->assertTrue($this->sut->add('myKey','otherValue'));

		$this->sut->setKeyPrefix('MYPREFIX');
		$this->assertEquals('myValue', $this->sut->get('myKey'));

		$this->sut->setKeyPrefix('OTHERPREFIX');
		$this->assertEquals('otherValue', $this->sut->get('myKey'));
	}

	/**
	 * It should get a KVP.
	 * @test
	 */
	public function canGet() {
		$this->sut->set('myKey','myValue');
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
	}

	/**
	 * It should replace a KVP.
	 * @test
	 */
	public function canReplaceWithKeyPrefix() {
		$this->sut = new Redis($this->client, 'MYPREFIX');
		$this->assertTrue($this->sut->set('myKey','myValue'));

		$other = new Redis($this->client, 'OTHERPREFIX');
		$this->assertFalse($other->replace('myKey', 'otherValue'));
		$this->assertTrue($other->set('myKey','otherValue'));

		$this->assertTrue($this->sut->replace('myKey','newValue'));

		$this->assertEquals('newValue',$this->sut->get('myKey'));
		$this->assertEquals('otherValue', $other->get('myKey'));
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
		$this->client->set('myKey', '"origValue"', 'ex', 100);
		$this->assertTrue($this->sut->replace('myKey','myValue', 45));
		$this->assertEquals('myValue', $this->sut->get('myKey'));
		$this->assertTTL('myKey', 45);
	}

	/**
	 * It should delete a KVP.
	 * @test
	 */
	public function canDelete() {
		$this->sut->set('myKey', 'myValue');
		$this->sut->set('otherKey', 'myValue');
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
		$this->sut->set('a','1');
		$this->sut->set('b','2');
		$this->sut->set('c','3');
		$this->assertKeyExists('a');
		$this->assertKeyExists('b');
		$this->assertKeyExists('c');

		$this->sut->flush();

		$this->assertKeyNotExists('a');
		$this->assertKeyNotExists('b');
		$this->assertKeyNotExists('c');
	}

	protected function assertIntValue($exp, $act){
		$this->assertTrue(is_int($act), sprintf("Type mismatch. Expected int, got %s.", gettype($act)));
		$this->assertSame($exp, $act);
	}

	/**
	 * It should get multiple keys at once.
	 * @test
	 */
	public function canGetMulti() {
		$this->sut->set('a', 'foo');
		$this->sut->set('b', 'bar');
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
		$this->sut->set('a', 'foo');
		$this->sut->set('b', 'bar');

		$other = new Redis($this->client, 'OTHERPREFIX');
		$other->set('a', 'FOO');
		$other->set('b', 'BAR');

		$result = $this->sut->get(['a','b']);
		$this->assertEquals('foo', $result['a']);
		$this->assertEquals('bar', $result['b']);

		$result = $other->get(['a','b']);
		$this->assertEquals('FOO', $result['a']);
		$this->assertEquals('BAR', $result['b']);
	}

	public function valueProvider(){
		return [
			'int' => [3],
			'string' => ['foo'],
			'arrayOfInts' => [[1,2,3,4,5]],
			'arrayOfStrings' => [['a','b','c']],
			'object' => [(object)['foo'=>'bar']],
			'MyClass' => [new MyClass('bar')]
		];
	}

	/**
	 * It should set and get different types.
	 * @test
	 * @dataProvider valueProvider
	 */
	public function canSetDifferentTypes($v) {
		$this->sut->set('myKey', $v);
		$r = $this->sut->get('myKey');
		$vType = gettype($v);
		$rType = gettype($r);
		$this->assertEquals($vType, $rType, "Type mismatch. Set $vType, got $rType.");
		$this->assertEquals($v, $r, sprintf("Value mismatch. Set %s, got %s.", print_r($v,TRUE), print_r($r, TRUE)));
		if(is_object($v)){
			$vClass = get_class($v);
			$rClass = get_class($r);
			$this->assertEquals($vClass, $rClass, "Class mismatch. Set $vClass, got $rClass.");
		}
	}

}

class MyClass {
	protected $foo;
	public function __construct($foo){
		$this->foo = $foo;
	}
}
