<?php

namespace BeSimple\SoapCommon\Mime;

use BeSimple\SoapBundle\Soap\SoapAttachment;

class PartFactory
{
    public static function createFromSoapAttachment(SoapAttachment $attachment)
    {
        return new Part(
            $attachment->getContent(),
            Part::CONTENT_TYPE_PDF,
            Part::CHARSET_UTF8,
            Part::ENCODING_BINARY,
            $attachment->getId()
        );
    }

    /**
     * @param SoapAttachment[] $attachments SOAP attachments
     * @return Part[]
     */
    public static function createAttachmentParts(array $attachments = [])
    {
        $parts = [];
        foreach ($attachments as $attachment) {
            $parts[] = self::createFromSoapAttachment($attachment);
        }

        return $parts;
    }
}
