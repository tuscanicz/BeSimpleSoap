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

namespace BeSimple\SoapCommon;

/**
 * @author Francis Besset <francis.besset@gmail.com>
 */
class ClassMap
{
    protected $classMap;
    protected $inverseClassMap;

    public function __construct(array $classMap = [])
    {
        $this->classmap = [];
        foreach ($classMap as $type => $className) {
            $this->add($type, $className);
        }
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->classMap;
    }

    /**
     * @param string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    public function get($type)
    {
        if (!$this->has($type)) {
            throw new \InvalidArgumentException(sprintf('The type "%s" does not exists', $type));
        }

        return $this->classMap[$type];
    }

    /**
     * @param string $type
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function add($type, $className)
    {
        if ($this->has($type)) {
            throw new \InvalidArgumentException(sprintf('The type "%s" already exists', $type));
        }

        $this->classMap[$type] = $className;
        $this->inverseClassMap[$className] = $type;
    }

    /**
     * @param string $type
     * @return boolean
     */
    public function has($type)
    {
        return isset($this->classmap[$type]);
    }

    public function getByClassName($className)
    {
        if (!$this->hasByClassName($className)) {
            throw new \InvalidArgumentException(sprintf('The className "%s" was not found in %s', $className, __CLASS__));
        }

        return $this->inverseClassMap[$className];
    }

    public function hasByClassName($className)
    {
        return isset($this->inverseClassMap[$className]);
    }

    public function addClassMap(ClassMap $classMap)
    {
        foreach ($classMap->getAll() as $type => $className) {
            $this->add($type, $className);
        }
    }
}
