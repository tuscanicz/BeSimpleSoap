<?php

/*
 * This file is part of the BeSimpleSoapCommon.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 * (c) Andreas Schamberger <mail@andreass.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapCommon;

use DOMDocument;

/**
 * Base class for SoapRequest and SoapResponse.
 *
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Andreas Schamberger <mail@andreass.net>
 */
abstract class SoapMessage
{
    /**
     * $_SERVER key for 'Content-Type' header.
     *
     * @var string
     */
    const CONTENT_TYPE_HEADER = 'CONTENT_TYPE';

    /**
     * $_SERVER key for 'Content-Type' header (with PHP cli-webserver)
     *
     * @var string
     */
    const HTTP_CONTENT_TYPE_HEADER = 'HTTP_CONTENT_TYPE';

    /**
     * $_SERVER key for 'SOAPAction' header.
     *
     * @var string
     */
    const SOAP_ACTION_HEADER = 'HTTP_SOAPACTION';

    /**
     * Content types for SOAP versions.
     *
     * @var array (string=>string)
     */
    static protected $versionToContentTypeMap = [
        SOAP_1_1 => 'text/xml; charset=utf-8',
        SOAP_1_2 => 'application/soap+xml; charset=utf-8'
    ];

    /**
     * SOAP action.
     *
     * @var string
     */
    protected $action;

    /**
     * Mime attachments.
     *
     * @var \BeSimple\SoapCommon\Mime\Part[]
     */
    protected $attachments;

    /**
     * Message content (MIME Message or SOAP Envelope).
     *
     * @var string
     */
    protected $content;

    /**
     * Message content type.
     *
     * @var string
     */
    protected $contentType;

    /**
     * Service location.
     *
     * @var string
     */
    protected $location;

    /**
     * SOAP version (SOAP_1_1|SOAP_1_2)
     *
     * @var int
     */
    protected $version;

    /**
    * Get content type for given SOAP version.
    *
    * @param int $version SOAP version constant SOAP_1_1|SOAP_1_2
    *
    * @return string
    * @throws \InvalidArgumentException
    */
    public static function getContentTypeForVersion($version)
    {
        if (!in_array($version, [SOAP_1_1, SOAP_1_2])) {
            throw new \InvalidArgumentException('Invalid SOAP version: ' . $version);
        }

        return self::$versionToContentTypeMap[$version];
    }

    /**
     * Get SOAP action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set SOAP action.
     *
     * @param string $action SOAP action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    public function hasAttachments()
    {
        return $this->attachments !== null;
    }

    /**
     * Get attachments.
     *
     * @return \BeSimple\SoapCommon\Mime\Part[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Set SOAP action.
     *
     * @param \BeSimple\SoapCommon\Mime\Part[] $attachments
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Get message content (MIME Message or SOAP Envelope).
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set message content (MIME Message or SOAP Envelope).
     *
     * @param string $content SOAP message
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get SOAP message as \DOMDocument
     *
     * @return \DOMDocument
     */
    public function getContentDocument()
    {
        $contentDomDocument = new DOMDocument();
        $contentDomDocument->loadXML($this->content);

        return $contentDomDocument;
    }

    /**
     * Get content type.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set content type.
     *
     * @param string $contentType Content type header
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set location.
     *
     * @param string $location Location string
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Get SOAP version SOAP_1_1|SOAP_1_2
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set version.
     *
     * @param int $version SOAP version SOAP_1_1|SOAP_1_2
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
