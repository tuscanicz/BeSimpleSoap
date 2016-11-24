<?php

/*
 * This file is part of BeSimpleSoapCommon.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapCommon\Mime;

use Exception;
use BeSimple\SoapCommon\Helper;

/**
 * Mime multi part container.
 *
 * Headers:
 * - MIME-Version
 * - Content-Type
 * - Content-ID
 * - Content-Location
 * - Content-Description
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class MultiPart extends PartHeader
{
    /**
     * Content-ID of main part.
     *
     * @var string
     */
    protected $mainPartContentId;

    /**
     * Mime parts.
     *
     * @var \BeSimple\SoapCommon\Mime\Part[]
     */
    protected $parts = [];

    /**
     * Construct new mime object.
     *
     * @param string $boundary
     */
    public function __construct($boundary = null)
    {
        $this->setHeader('MIME-Version', '1.0');
        $this->setHeader('Content-Type', 'multipart/related');
        $this->setHeader('Content-Type', 'type', 'text/xml');
        $this->setHeader('Content-Type', 'charset', 'utf-8');
        if ($boundary !== null) {
            $this->setHeader('Content-Type', 'boundary', $boundary);
        }
    }

    /**
     * Get mime message of this object (without headers).
     *
     * @param boolean $withHeaders Returned mime message contains headers
     *
     * @return string
     */
    public function getMimeMessage($withHeaders = false)
    {
        $message = ($withHeaders === true) ? $this->generateHeaders() : "";
        // add parts
        foreach ($this->parts as $part) {
            $message .= "\n" . '--' . $this->getHeader('Content-Type', 'boundary') . "\n";
            $message .= $part->getMessagePart();
        }
        $message .= "\n" . '--' . $this->getHeader('Content-Type', 'boundary') . '--';

        return $message;
    }

    /**
     * Get string array with MIME headers for usage in HTTP header (with CURL).
     * Only 'Content-Type' and 'Content-Description' headers are returned.
     *
     * @return string[]
     */
    public function getHeadersForHttp()
    {
        $allowedHeaders = [
            'Content-Type',
            'Content-Description',
        ];
        $headers = [];
        foreach ($this->headers as $fieldName => $value) {
            if (in_array($fieldName, $allowedHeaders)) {
                $fieldValue = $this->generateHeaderFieldValue($value);
                // for http only ISO-8859-1
                $headers[] = $fieldName . ': '. iconv('utf-8', 'ISO-8859-1//TRANSLIT', $fieldValue);
            }
        }

        return $headers;
    }

    /**
     * Add new part to MIME message.
     *
     * @param \BeSimple\SoapCommon\Mime\Part $part   Part that is added
     * @param boolean                        $isMain Is the given part the main part of mime message
     *
     * @return void
     */
    public function addPart(Part $part, $isMain = false)
    {
        $contentId = trim($part->getHeader('Content-ID'), '<>');
        if ($isMain === true) {
            $this->mainPartContentId = $contentId;
            $this->setHeader('Content-Type', 'start', $part->getHeader('Content-ID'));
        } else {
            $part->setHeader('Content-Location', $contentId);
        }
        $this->parts[$contentId] = $part;
    }

    /**
     * Get part with given content id.
     *
     * @param string $contentId Content id of desired part
     *
     * @return \BeSimple\SoapCommon\Mime\Part
     */
    public function getPart($contentId)
    {
        if (isset($this->parts[$contentId])) {
            return $this->parts[$contentId];
        }

        throw new Exception('MimePart not found by ID: ' . $contentId);
    }

    /**
     * Get main part.
     *
     * @return \BeSimple\SoapCommon\Mime\Part
     */
    public function getMainPart()
    {
        foreach ($this->parts as $cid => $part) {
            if ($cid === $this->mainPartContentId) {
                return $part;
            }
        }

        throw new Exception('SoapRequest error: main part not found by Id: ' . $this->mainPartContentId);
    }

    /**
     * Get attachment parts.
     *
     * @return \BeSimple\SoapCommon\Mime\Part[]
     */
    public function getAttachments()
    {
        $parts = [];
        foreach ($this->parts as $cid => $part) {
            if ($cid !== $this->mainPartContentId) {
                $parts[$cid] = $part;
            }
        }

        return $parts;
    }

    /**
     * Returns a unique boundary string.
     *
     * @return string
     */
    public function generateBoundary()
    {
        return 'urn:uuid:' . Helper::generateUUID();
    }
}