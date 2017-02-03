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

use BeSimple\SoapCommon\Mime\Parser\ContentTypeParser;
use BeSimple\SoapCommon\Mime\Parser\ParsedPart;
use BeSimple\SoapCommon\Mime\Parser\ParsedPartList;
use Exception;

/**
 * Simple Multipart-Mime parser.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 * @author Petr Bechyne <mail@petrbechyne.com>
 */
class Parser
{
    const HAS_HTTP_REQUEST_HEADERS = true;
    const HAS_NO_HTTP_REQUEST_HEADERS = false;

    /**
     * Parse the given Mime-Message and return a \BeSimple\SoapCommon\Mime\MultiPart object.
     *
     * @param string                $mimeMessage Mime message string
     * @param string[] $headers     array(string=>string) of header elements (e.g. coming from http request)
     *
     * @return \BeSimple\SoapCommon\Mime\MultiPart
     */
    public static function parseMimeMessage($mimeMessage, array $headers = [])
    {
        $multiPart = new MultiPart();
        $mimeMessageLines = preg_split("/(\n)/", $mimeMessage);
        // add given headers, e.g. coming from HTTP headers
        if (count($headers) > 0) {
            self::setMultiPartHeaders($multiPart, $headers);
            $hasHttpRequestHeaders = self::HAS_HTTP_REQUEST_HEADERS;
        } else {
            $hasHttpRequestHeaders = self::HAS_NO_HTTP_REQUEST_HEADERS;
        }
        if (self::hasBoundary($mimeMessageLines)) {
            $parsedPartList = self::getPartsFromMimeMessageLines(
                $multiPart,
                $mimeMessageLines,
                $hasHttpRequestHeaders
            );
            if ($parsedPartList->hasParts() === false) {
                throw new Exception(
                    'Could not parse MimeMessage: no Parts for MultiPart given'
                );
            }
            if ($parsedPartList->hasExactlyOneMainPart() === false) {
                throw new Exception(
                    sprintf(
                        'Could not parse MimeMessage %s HTTP headers: unexpected count of main ParsedParts: %s (total: %d)',
                        $hasHttpRequestHeaders ? 'with' : 'w/o',
                        implode(', ', $parsedPartList->getPartContentIds()),
                        $parsedPartList->getMainPartCount()
                    )
                );
            }
            self::appendPartsToMultiPart(
                $parsedPartList,
                $multiPart
            );
        } else {
            self::appendSingleMainPartToMultiPart(new Part($mimeMessage), $multiPart);
        }

        return $multiPart;
    }

    /**
     * @param MultiPart $multiPart
     * @param string[] $mimeMessageLines
     * @param bool $hasHttpHeaders = self::HAS_HTTP_REQUEST_HEADERS|self::HAS_NO_HTTP_REQUEST_HEADERS
     * @return ParsedPartList
     */
    private static function getPartsFromMimeMessageLines(
        MultiPart $multiPart,
        array $mimeMessageLines,
        $hasHttpHeaders
    ) {
        $parsedParts = [];
        $contentTypeBoundary = $multiPart->getHeader('Content-Type', 'boundary');
        $contentTypeContentIdStart = $multiPart->getHeader('Content-Type', 'start');
        $currentPart = $multiPart;
        $messagePartStringContent = '';
        $inHeader = $hasHttpHeaders;
        $hitFirstBoundary = false;

        foreach ($mimeMessageLines as $mimeMessageLine) {
            // ignore http status code and POST *
            if (substr($mimeMessageLine, 0, 5) == 'HTTP/' || substr($mimeMessageLine, 0, 4) == 'POST') {
                continue;
            }
            if (isset($currentHeader)) {
                if (isset($mimeMessageLine[0]) && ($mimeMessageLine[0] === ' ' || $mimeMessageLine[0] === "\t")) {
                    $currentHeader .= $mimeMessageLine;
                    continue;
                }
                if (strpos($currentHeader, ':') !== false) {
                    list($headerName, $headerValue) = explode(':', $currentHeader, 2);
                    $headerValue = iconv_mime_decode($headerValue, 0, Part::CHARSET_UTF8);
                    $parsedMimeHeaders = ContentTypeParser::parseContentTypeHeader($headerName, $headerValue);
                    foreach ($parsedMimeHeaders as $parsedMimeHeader) {
                        $currentPart->setHeader(
                            $parsedMimeHeader->getName(),
                            $parsedMimeHeader->getValue(),
                            $parsedMimeHeader->getSubValue()
                        );
                    }
                    $contentTypeBoundary = $multiPart->getHeader('Content-Type', 'boundary');
                    $contentTypeContentIdStart = $multiPart->getHeader('Content-Type', 'start');
                }
                unset($currentHeader);
            }
            if ($inHeader === true) {
                if (trim($mimeMessageLine) == '') {
                    $inHeader = false;
                    continue;
                }
                $currentHeader = $mimeMessageLine;
                continue;
            } else {
                if (self::isBoundary($mimeMessageLine)) {
                    if (self::isMiddleBoundary($mimeMessageLine, $contentTypeBoundary)) {
                        if ($currentPart instanceof Part) {
                            $currentPartContent = self::decodeContent(
                                $currentPart,
                                substr($messagePartStringContent, 0, -1)
                            );
                            $currentPart->setContent($currentPartContent);
                            // check if there is a start parameter given, if not set first part
                            if ($contentTypeContentIdStart === null || $currentPart->hasContentId($contentTypeContentIdStart) === true) {
                                $contentTypeContentIdStart = $currentPart->getHeader('Content-ID');
                                $parsedParts[] = new ParsedPart($currentPart, ParsedPart::PART_IS_MAIN);
                            } else {
                                $parsedParts[] = new ParsedPart($currentPart, ParsedPart::PART_IS_NOT_MAIN);
                            }
                        }
                        $currentPart = new Part();
                        $hitFirstBoundary = true;
                        $inHeader = true;
                        $messagePartStringContent = '';
                    } else if (self::isLastBoundary($mimeMessageLine, $contentTypeBoundary)) {
                        $currentPartContent = self::decodeContent(
                            $currentPart,
                            substr($messagePartStringContent, 0, -1)
                        );
                        $currentPart->setContent($currentPartContent);
                        // check if there is a start parameter given, if not set first part
                        if ($contentTypeContentIdStart === null || $currentPart->hasContentId($contentTypeContentIdStart) === true) {
                            $contentTypeContentIdStart = $currentPart->getHeader('Content-ID');
                            $parsedParts[] = new ParsedPart($currentPart, ParsedPart::PART_IS_MAIN);
                        } else {
                            $parsedParts[] = new ParsedPart($currentPart, ParsedPart::PART_IS_NOT_MAIN);
                        }
                        $messagePartStringContent = '';
                    } else {
                        // else block migrated from https://github.com/progmancod/BeSimpleSoap/commit/bf9437e3bcf35c98c6c2f26aca655ec3d3514694
                        // be careful to replace \r\n with \n
                        $messagePartStringContent .= $mimeMessageLine . "\n";
                    }
                } else {
                    if ($hitFirstBoundary === false) {
                        if (trim($mimeMessageLine) !== '') {
                            $inHeader = true;
                            $currentHeader = $mimeMessageLine;
                            continue;
                        }
                    }
                    $messagePartStringContent .= $mimeMessageLine . "\n";
                }
            }
        }

        return new ParsedPartList($parsedParts);
    }

    /**
     * @param ParsedPartList $parsedPartList
     * @param MultiPart $multiPart
     */
    private static function appendPartsToMultiPart(ParsedPartList $parsedPartList, MultiPart $multiPart)
    {
        foreach ($parsedPartList->getParts() as $parsedPart) {
            $multiPart->addPart(
                $parsedPart->getPart(),
                $parsedPart->isMain()
            );
        }
    }

    private static function appendSingleMainPartToMultiPart(Part $part, MultiPart $multiPart)
    {
        $multiPart->addPart($part, true);
    }

    private static function setMultiPartHeaders(MultiPart $multiPart, $headers)
    {
        foreach ($headers as $name => $value) {
            if ($name === 'Content-Type') {
                $parsedMimeHeaders = ContentTypeParser::parseContentTypeHeader($name, $value);
                foreach ($parsedMimeHeaders as $parsedMimeHeader) {
                    $multiPart->setHeader(
                        $parsedMimeHeader->getName(),
                        $parsedMimeHeader->getValue(),
                        $parsedMimeHeader->getSubValue()
                    );
                }
            } else {
                $multiPart->setHeader($name, $value);
            }
        }
    }

    /**
     * Decodes the content of a Mime part
     *
     * @param Part $part    Part to add content
     * @param string $partStringContent Content to decode
     * @return string $partStringContent decodedContent
     */
    private static function decodeContent(Part $part, $partStringContent)
    {
        $encoding = strtolower($part->getHeader('Content-Transfer-Encoding'));
        $charset = strtolower($part->getHeader('Content-Type', 'charset'));

        if ($encoding === Part::ENCODING_BASE64) {
            $partStringContent = base64_decode($partStringContent);
        } else if ($encoding === Part::ENCODING_QUOTED_PRINTABLE) {
            $partStringContent = quoted_printable_decode($partStringContent);
        }

        if ($charset !== Part::CHARSET_UTF8) {
            return iconv($charset, Part::CHARSET_UTF8, $partStringContent);
        }

        return $partStringContent;
    }

    private static function hasBoundary(array $lines)
    {
        foreach ($lines as $line) {
            if (self::isBoundary($line)) {

                return true;
            }
        }

        return false;
    }

    private static function isBoundary($mimeMessageLine)
    {
        return strlen($mimeMessageLine) > 0 && $mimeMessageLine[0] === "-";
    }

    private static function isMiddleBoundary($mimeMessageLine, $contentTypeBoundary)
    {
        return strcmp(trim($mimeMessageLine), '--'.$contentTypeBoundary) === 0;
    }

    private static function isLastBoundary($mimeMessageLine, $contentTypeBoundary)
    {
        return strcmp(trim($mimeMessageLine), '--'.$contentTypeBoundary.'--') === 0;
    }
}