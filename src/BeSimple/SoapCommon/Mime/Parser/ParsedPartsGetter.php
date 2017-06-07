<?php

namespace BeSimple\SoapCommon\Mime\Parser;

use BeSimple\SoapCommon\Mime\Boundary\MimeBoundaryAnalyser;
use BeSimple\SoapCommon\Mime\MultiPart;
use BeSimple\SoapCommon\Mime\Part;
use Exception;

class ParsedPartsGetter
{
    const HAS_HTTP_REQUEST_HEADERS = true;
    const HAS_NO_HTTP_REQUEST_HEADERS = false;

    /**
     * @param MultiPart $multiPart
     * @param string[] $mimeMessageLines
     * @param bool $hasHttpHeaders = self::HAS_HTTP_REQUEST_HEADERS|self::HAS_NO_HTTP_REQUEST_HEADERS
     * @return ParsedPartList
     */
    public static function getPartsFromMimeMessageLines(
        MultiPart $multiPart,
        array $mimeMessageLines,
        $hasHttpHeaders
    ) {
        $parsedParts = [];
        $contentTypeBoundary = $multiPart->getHeader('Content-Type', 'boundary');
        if ($contentTypeBoundary === null) {
            throw new Exception(
                'Unable to get Content-Type boundary from given MultiPart: ' . var_export($multiPart->getHeaders(), true)
            );
        }
        $contentTypeContentIdStart = $multiPart->getHeader('Content-Type', 'start');
        if ($contentTypeContentIdStart === null) {
            throw new Exception(
                'Unable to get Content-Type start from given MultiPart: ' . var_export($multiPart->getHeaders(), true)
            );
        }
        $currentPart = $multiPart;
        $messagePartStringContent = '';
        $inHeader = $hasHttpHeaders;
        $hitFirstBoundary = false;
        foreach ($mimeMessageLines as $mimeMessageLine) {
            if (substr($mimeMessageLine, 0, 5) === 'HTTP/' || substr($mimeMessageLine, 0, 4) === 'POST') {
                continue;
            }
            if (isset($currentHeader)) {
                if (isset($mimeMessageLine[0]) && ($mimeMessageLine[0] === ' ' || $mimeMessageLine[0] === "\t")) {
                    $currentHeader .= $mimeMessageLine;
                    continue;
                }
                if (strpos($currentHeader, ':') !== false) {
                    list($headerName, $headerValue) = explode(':', $currentHeader, 2);
                    $headerValueWithNoCrAtTheEnd = trim($headerValue);
                    try {
                        $headerValue = iconv_mime_decode($headerValueWithNoCrAtTheEnd, 0, Part::CHARSET_UTF8);
                    } catch (Exception $e) {
                        if ($hitFirstBoundary === false) {
                            throw new Exception(
                                'Unable to parse message: cannot parse headers before hitting the first boundary'
                            );
                        }
                        throw new Exception(
                            sprintf(
                                'Unable to get header value: possible parsing message contents of %s characters in header parser: %s',
                                mb_strlen($headerValueWithNoCrAtTheEnd),
                                $e->getMessage()
                            )
                        );
                    }
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
                if (trim($mimeMessageLine) === '') {
                    $inHeader = false;
                    continue;
                }
                $currentHeader = $mimeMessageLine;
                continue;
            } else {
                if (MimeBoundaryAnalyser::isMessageLineBoundary($mimeMessageLine)) {
                    if (MimeBoundaryAnalyser::isMessageLineMiddleBoundary($mimeMessageLine, $contentTypeBoundary)) {
                        if ($currentPart instanceof Part) {
                            $currentPartContent = self::decodeContent(
                                $currentPart,
                                substr($messagePartStringContent, 0, -1)
                            );
                            if ($currentPartContent[strlen($currentPartContent) - 1] === "\r") {
                                // temporary hack: if there is a CRLF before any middle boundary, then the remaining CR must be removed
                                $currentPartContent = substr($currentPartContent, 0, -1);
                            }
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
                    } elseif (MimeBoundaryAnalyser::isMessageLineLastBoundary($mimeMessageLine, $contentTypeBoundary)) {
                        $currentPartContent = self::decodeContent(
                            $currentPart,
                            substr($messagePartStringContent, 0, -1)
                        );
                        if ($currentPartContent[strlen($currentPartContent) - 1] === "\r") {
                            // temporary hack: if there is a CRLF before last boundary, then the remaining CR must be removed
                            $currentPartContent = substr($currentPartContent, 0, -1);
                        }
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
        } elseif ($encoding === Part::ENCODING_QUOTED_PRINTABLE) {
            $partStringContent = quoted_printable_decode($partStringContent);
        }

        if ($charset !== Part::CHARSET_UTF8) {
            return iconv($charset, Part::CHARSET_UTF8, $partStringContent);
        }

        return $partStringContent;
    }
}
