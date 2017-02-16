<?php

trait TestCaseTrait
{
    public function testAdd()
    {
        $this->cache->del('foo');
        $this->assertTrue($this->cache->add('foo', 123));
        $this->assertFalse($this->cache->add('foo', 456));
        $this->assertEquals(123, $this->cache->get('foo'));
        $this->cache->del('foo');
    }

    public function testSet()
    {
        $this->cache->del('foo');
        $this->cache->set('foo', 123);
        $this->assertEquals(123, $this->cache->get('foo'));

        $this->cache->set('foo', 456);
        $this->assertEquals(456, $this->cache->get('foo'));

        $arr1 = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->cache->set('arr1', $arr1);
        $this->assertEquals($arr1, $this->cache->get('arr1'));
        $this->cache->del('arr1');
    }

    public function testMulti()
    {
        $items = ['a' => 'hello', 'b' => 123, 'c' => ['k1' => 'v1', 'k2' => 'v2']];
        $this->assertEquals(count($items), $this->cache->mset($items));
        $this->assertEquals($items, $this->cache->mget(array_keys($items)));
        // 读取不存在的key
        $data = $this->cache->mget(['a', 'b', 'd']);
        $this->assertEquals($items['a'], $data['a']);
        $this->assertEquals($items['b'], $data['b']);
        $this->assertArrayNotHasKey('d', $data);
    }

    public function testGet()
    {
        $this->cache->del('no_exist');
        $this->assertFalse($this->cache->get('no_exist'));
    }

    public function testDel()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        // 第1种语法
        $this->cache->mset($items);
        $this->assertEquals(count($items), $this->cache->del(array_keys($items)));
        // 第2种语法
        $this->cache->mset($items);
        $this->assertEquals(count($items), $this->cache->del('a', 'b', 'c'));
    }

    public function testIncrement()
    {
        $this->cache->del('inc');
        for ($i = 1; $i < 100; $i++) {
            $this->assertEquals($i, $this->cache->increment('inc'));
        }
        $this->cache->del('inc');
        for ($i = 2; $i < 100; $i += 2) {
            $this->assertEquals($i, $this->cache->increment('inc', 2));
        }
    }

    public function testDecrement()
    {
        $this->cache->set('inc', 101);
        for ($i = 100; $i > -100; $i--) {
            $this->assertEquals($i, $this->cache->decrement('inc'));
        }
        $this->cache->del('inc');
        for ($i = 2; $i < 100; $i += 2) {
            $this->assertEquals(0-$i, $this->cache->decrement('inc', 2));
        }
    }
}