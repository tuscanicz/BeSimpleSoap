<?php

namespace BeSimple\SoapCommon\Mime\Parser;

class ParsedMimeHeader
{
    private $name;
    private $value;
    private $subValue;

    /**
     * @param string $name
     * @param string $value
     * @param string|null $subValue
     */
    public function __construct($name, $value, $subValue = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->subValue = $subValue;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getSubValue()
    {
        return $this->subValue;
    }
}
