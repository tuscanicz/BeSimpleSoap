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
        $classmap = new ClassMap();

        $this->assertSame(array(), $classmap->getAll());
    }

    public function testAdd()
    {
        $classmap = new ClassMap();

        $classmap->add('foobar', 'BeSimple\SoapCommon\ClassMap');

        $this->setExpectedException('InvalidArgumentException');
        $classmap->add('foobar', 'BeSimple\SoapCommon\ClassMap');
    }

    public function testGet()
    {
        $classmap = new ClassMap();

        $classmap->add('foobar', 'BeSimple\SoapCommon\ClassMap');
        $this->assertSame('BeSimple\SoapCommon\ClassMap', $classmap->get('foobar'));

        $this->setExpectedException('InvalidArgumentException');
        $classmap->get('bar');
    }

    public function testAddClassMap()
    {
        $classmap1 = new ClassMap();
        $classmap2 = new ClassMap();

        $classmap2->add('foobar', 'BeSimple\SoapCommon\ClassMap');
        $classmap1->addClassMap($classmap2);

        $this->assertEquals(array('foobar' => 'BeSimple\SoapCommon\ClassMap'), $classmap1->getAll());

        $this->setExpectedException('InvalidArgumentException');
        $classmap1->addClassMap($classmap2);
    }
}