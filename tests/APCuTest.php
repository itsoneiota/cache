<?php

namespace itsoneiota\cache;

function apcu_add($key, $value)
{
    if (array_key_exists($key, APCuTest::$apcu)) {
        return false;
    } else {
        APCuTest::$apcu[$key] = $value;
        return true;
    }
}

function apcu_delete($key)
{
    if (array_key_exists($key, APCuTest::$apcu)) {
        unset(APCuTest::$apcu[$key]);
        return true;
    } else {
        return false;
    }
}

function apcu_clear_cache()
{
    APCuTest::$apcu = [];
    return true;
}

function apcu_fetch($key)
{
    if (is_array($key)) {
        $result = [];
        foreach ($key as $index) {
            if (array_key_exists($index, APCuTest::$apcu)) {
                $result[$index] = APCuTest::$apcu[$index];
            }
        }
        if (count($result) == 0) {
            return false;
        } else {
            return $result;
        }
    } else {
        if (array_key_exists($key, APCuTest::$apcu)) {
            return APCuTest::$apcu[$key];
        } else {
            return false;
        }
    }
}

function apcu_store($key, $value)
{
    APCuTest::$apcu[$key] = $value;
    return true;
}

class APCuTest extends \PHPUnit\Framework\TestCase
{
    protected $sut;
    public static $apcu = []; //Mock APCu cache used by mock APCu functions

    public function setUp()
    {
        $this->sut = new APCu();
    }

    /**
     * It should add a KVP.
     * @test
     */
    public function testCanAdd()
    {
        self::$apcu = [];
        $this->assertTrue($this->sut->add('myKey','myValue'));
    }

    /**
     * It should get a KVP.
     * @test
     */
    public function testCanGet()
    {
        self::$apcu = [];
        $this->sut->add('myKey', 'myValue');
        $this->assertSame('myValue', $this->sut->get('myKey'));
    }

    /**
     * It should replace a KVP.
     * @test
     */
    public function testCanReplace()
    {
        self::$apcu = [];
        $this->sut->add('myKey', 'myValue');
        $this->assertTrue($this->sut->replace('myKey', 'myNewValue'));
        $this->assertSame('myNewValue', self::$apcu['myKey']);
    }

    /**
     * It should delete a KVP.
     * @test
     */
    public function testCanDelete()
    {
        self::$apcu = [];
        $this->sut->add('myKey', 'myValue');
        $this->assertTrue($this->sut->delete('myKey'));
        $this->assertCount(0, self::$apcu);
    }

    /**
     * It should flush the cache.
     * @test
     */
    public function testCanFlush() {
        self::$apcu = [];
        $this->sut->add('myKey', 'myValue');
        $this->sut->add('anotherKey', 'anotherValue');
        $this->assertTrue($this->sut->flush());
        $this->assertCount(0, self::$apcu);
    }
}
