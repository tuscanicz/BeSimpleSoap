<?php

namespace BeSimple\SoapCommon\Mime\Parser;

use Exception;

class ParsedPartList
{
    private $parts;

    /**
     * @param ParsedPart[] $parts
     */
    public function __construct(array $parts)
    {
        $this->parts = $parts;
    }

    public function getMainPartCount()
    {
        $mainPartsCount = 0;
        foreach ($this->getParts() as $parsedPart) {
            if ($parsedPart->isMain() === true) {
                $mainPartsCount++;
            }
        }

        return $mainPartsCount;
    }

    public function hasExactlyOneMainPart()
    {
        return $this->getMainPartCount() === 1;
    }

    public function getPartContentIds()
    {
        $partContentIds = [];
        foreach ($this->getParts() as $parsedPart) {
            $partContentIds[] = $parsedPart->getPart()->getContentId();
        }

        return $partContentIds;
    }

    public function getParts()
    {
        return $this->parts;
    }

    public function getPartCount()
    {
        return count($this->parts);
    }

    public function hasParts()
    {
        return $this->getPartCount() > 0;
    }
}
