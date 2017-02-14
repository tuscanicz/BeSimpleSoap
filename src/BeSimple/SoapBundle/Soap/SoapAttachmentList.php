<?php

namespace BeSimple\SoapBundle\Soap;

class SoapAttachmentList
{
    private $soapAttachments;

    /**
     * @param SoapAttachment[] $soapAttachments
     */
    public function __construct(array $soapAttachments = [])
    {
        $this->soapAttachments = $soapAttachments;
    }

    public function hasSoapAttachments()
    {
        return $this->soapAttachments !== null && count($this->soapAttachments) > 0;
    }

    public function getSoapAttachments()
    {
        return $this->soapAttachments;
    }

    public function getSoapAttachmentIds()
    {
        $ids = [];
        foreach ($this->getSoapAttachments() as $soapAttachment) {
            $ids[] = $soapAttachment->getId();
        }

        return $ids;
    }
}
