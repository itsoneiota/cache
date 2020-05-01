<?php

namespace itsoneiota\cache;

function apcu_add($key, $value)
{
    if (array_key_exists($key, APCuCounterTest::$apcu)) {
        return false;
    } else {
        APCuCounterTest::$apcu[$key] = $value;
        return true;
    }
}

function apcu_fetch($key)
{
    if (is_array($key)) {
        $result = [];
        foreach ($key as $index) {
            if (array_key_exists($index, APCuCounterTest::$apcu)) {
                $result[$index] = APCuCounterTest::$apcu[$index];
            }
        }
        if (count($result) == 0) {
            return false;
        } else {
            return $result;
        }
    } else {
        if (array_key_exists($key, APCuCounterTest::$apcu)) {
            return APCuCounterTest::$apcu[$key];
        } else {
            return false;
        }
    }
}

function apcu_exists($key)
{
    if (array_key_exists($key, APCuCounterTest::$apcu)) {
        return true;
    } else {
        return false;
    }
}

function apcu_inc($key, $offset)
{
    if (array_key_exists($key, APCuCounterTest::$apcu)) {
        return (int)APCuCounterTest::$apcu[$key] + (int)$offset;
    } else {
        return false;
    }
}

function apcu_dec($key, $offset)
{
    if (array_key_exists($key, APCuCounterTest::$apcu)) {
        return (int)APCuCounterTest::$apcu[$key] - (int)$offset;
    } else {
        return false;
    }
}

class APCuCounterTest extends \PHPUnit\Framework\TestCase
{
    protected $sut;
    public static $apcu = []; //Mock APCu cache used by mock APCu functions

    public function setUp()
    {
        $this->sut = new APCuCounter();
    }

    /**
     * It should increment.
     * @test
     */
    public function testCanIncrementExisting()
    {
        self::$apcu = [];
        apcu_add('myKey', 5);
        $this->assertEquals(7, $this->sut->increment('myKey', 2));
    }

    /**
     * It should create and increment.
     * @test
     */
    public function testCanIncrementNonExisting()
    {
        self::$apcu = [];
        $this->assertEquals(7, $this->sut->increment('myKey', 2, 5));
    }

    /**
     * It should decrement.
     * @test
     */
    public function testCanDecrementExisting()
    {
        self::$apcu = [];
        apcu_add('myKey', 5);
        $this->assertEquals(3, $this->sut->decrement('myKey', 2));
    }

    /**
     * It should create and decrement.
     * @test
     */
    public function testCanDecrementNonExisting() {
        self::$apcu = [];
        $this->assertEquals(3, $this->sut->decrement('myKey', 2, 5));
    }
}
