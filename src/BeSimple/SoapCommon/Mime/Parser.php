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

use BeSimple\SoapCommon\Mime\Boundary\MimeBoundaryAnalyser;
use BeSimple\SoapCommon\Mime\Parser\ContentTypeParser;
use BeSimple\SoapCommon\Mime\Parser\ParsedPartList;
use BeSimple\SoapCommon\Mime\Parser\ParsedPartsGetter;

/**
 * Simple Multipart-Mime parser.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 * @author Petr Bechyne <mail@petrbechyne.com>
 */
class Parser
{
    /**
     * Parse the given Mime-Message and return a \BeSimple\SoapCommon\Mime\MultiPart object.
     *
     * @param string $mimeMessage Mime message string
     * @param string[] $headers array(string=>string) of header elements (e.g. coming from http request)
     * @return MultiPart
     */
    public static function parseMimeMessage($mimeMessage, array $headers = [])
    {
        $multiPart = new MultiPart();
        $mimeMessageLines = explode("\n", $mimeMessage);
        $mimeMessageLineCount = count($mimeMessageLines);

        // add given headers, e.g. coming from HTTP headers
        if (count($headers) > 0) {
            self::setMultiPartHeaders($multiPart, $headers);
            $hasHttpRequestHeaders = ParsedPartsGetter::HAS_HTTP_REQUEST_HEADERS;
        } else {
            $hasHttpRequestHeaders = ParsedPartsGetter::HAS_NO_HTTP_REQUEST_HEADERS;
        }
        if (MimeBoundaryAnalyser::hasMessageBoundary($mimeMessageLines) === true) {
            if ($mimeMessageLineCount <= 1) {
                throw new CouldNotParseMimeMessageException(
                    sprintf(
                        'Cannot parse MultiPart message of %d characters: got unexpectable low number of lines: %s',
                        mb_strlen($mimeMessage),
                        (string)$mimeMessageLineCount
                    ),
                    $mimeMessage,
                    $headers
                );
            }
            $parsedPartList = ParsedPartsGetter::getPartsFromMimeMessageLines(
                $multiPart,
                $mimeMessageLines,
                $hasHttpRequestHeaders
            );
            if ($parsedPartList->hasParts() === false) {
                throw new CouldNotParseMimeMessageException(
                    'Could not parse MimeMessage: no Parts for MultiPart given',
                    $mimeMessage,
                    $headers
                );
            }
            if ($parsedPartList->hasExactlyOneMainPart() === false) {
                throw new CouldNotParseMimeMessageException(
                    sprintf(
                        'Could not parse MimeMessage %s HTTP headers: unexpected count of main ParsedParts: %s (total: %d)',
                        $hasHttpRequestHeaders ? 'with' : 'w/o',
                        implode(', ', $parsedPartList->getPartContentIds()),
                        $parsedPartList->getMainPartCount()
                    ),
                    $mimeMessage,
                    $headers
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
}
