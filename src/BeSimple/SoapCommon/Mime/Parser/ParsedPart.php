<?php

namespace BeSimple\SoapCommon\Mime\Parser;

use BeSimple\SoapCommon\Mime\Part;

class ParsedPart
{
    const PART_IS_MAIN = true;
    const PART_IS_NOT_MAIN = false;

    private $part;
    private $isMain;

    /**
     * @param Part $part
     * @param bool $isMain
     */
    public function __construct(Part $part, $isMain)
    {
        $this->part = $part;
        $this->isMain = $isMain;
    }

    public function getPart()
    {
        return $this->part;
    }

    public function isMain()
    {
        return $this->isMain;
    }
}
