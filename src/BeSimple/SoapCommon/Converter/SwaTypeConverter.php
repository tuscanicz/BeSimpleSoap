<?php

namespace BeSimple\SoapCommon\Converter;

use BeSimple\SoapCommon\Mime\Part as MimePart;

/**
 * SwA type converter.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class SwaTypeConverter implements TypeConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTypeNamespace()
    {
        return 'http://www.w3.org/2001/XMLSchema';
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeName()
    {
        return 'base64Binary';
    }

    /**
     * {@inheritDoc}
     */
    public function convertXmlToPhp($data)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($data);

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function convertPhpToXml($data)
    {
        $part = new MimePart($data);
        $contentId = trim($part->getHeader('Content-ID'), '<>');

        return sprintf('<%s href="%s"/>', $this->getTypeName(), 'cid:' . $contentId);
    }
}
