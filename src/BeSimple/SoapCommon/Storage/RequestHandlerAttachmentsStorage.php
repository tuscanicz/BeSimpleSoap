<?php

namespace BeSimple\SoapCommon\Storage;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapCommon\Storage\AbstractStorage\AbstractStorage;

class RequestHandlerAttachmentsStorage extends AbstractStorage
{
    /**
     * @param SoapAttachment[] $attachments
     */
    public function __construct(array $attachments)
    {
        parent::setItems($attachments);
    }

    /**
     * @return SoapAttachment[]
     */
    public function getAttachments()
    {
        return parent::getItems();
    }
}
