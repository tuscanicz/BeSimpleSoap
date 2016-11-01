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

        // convert href -> myhref for external references as PHP throws exception in this case
        // http://svn.php.net/viewvc/php/php-src/branches/PHP_5_4/ext/soap/php_encoding.c?view=markup#l3436
        $ref = $doc->documentElement->getAttribute('myhref');

        if ('cid:' === substr($ref, 0, 4)) {
            $contentId = urldecode(substr($ref, 4));

            // @todo-critical: ci je nyni zodpovednost vygetovat attachmenty
            if (null !== ($part = $this->soapKernel->getAttachment($contentId))) {

                return $part->getContent();
            } else {

                return null;
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function convertPhpToXml($data)
    {
        $part = new MimePart($data);
        $contentId = trim($part->getHeader('Content-ID'), '<>');

        // @todo-critical: ci je nyni zodpovednost nastrkat attachmenty
        //$this->soapKernel->addAttachment($part);

        return sprintf('<%s href="%s"/>', $this->getTypeName(), 'cid:' . $contentId);
    }
}
