<?php

/*
 * This file is part of the BeSimpleSoapCommon.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapCommon\Tests;

use BeSimple\SoapCommon\ClassMap;

/**
 * UnitTest for \BeSimple\SoapCommon\ClassMap.
 *
 * @author Francis Besset <francis.besset@gmail.com>
 */
class ClassMapTest extends \PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $classMap = new ClassMap();

        $this->assertSame([], $classMap->getAll());
    }

    public function testAdd()
    {
        $classMap = new ClassMap();

        $classMap->add('foobar', 'BeSimple\SoapCommon\ClassMap');

        $this->setExpectedException('InvalidArgumentException');
        $classMap->add('foobar', 'BeSimple\SoapCommon\ClassMap');
    }

    public function testGet()
    {
        $classMap = new ClassMap();

        $classMap->add('foobar', 'BeSimple\SoapCommon\ClassMap');
        $this->assertSame('BeSimple\SoapCommon\ClassMap', $classMap->get('foobar'));

        $this->setExpectedException('Exception');
        $classMap->get('bar');
    }

    public function testAddClassMap()
    {
        $classMap1 = new ClassMap();
        $classMap2 = new ClassMap();

        $classMap2->add('foobar', 'BeSimple\SoapCommon\ClassMap');
        $classMap1->addClassMap($classMap2);

        $this->assertEquals(['foobar' => 'BeSimple\SoapCommon\ClassMap'], $classMap1->getAll());

        $this->setExpectedException('Exception');
        $classMap1->addClassMap($classMap2);
    }
}
