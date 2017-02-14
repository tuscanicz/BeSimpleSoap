<?php

namespace BeSimple\SoapCommon\Mime\Parser;

class ContentTypeParser
{
    /**
     * Parse a "Content-Type" header with multiple sub values.
     * e.g. Content-Type: multipart/related; boundary=boundary; type=text/xml;
     * start="<123@abc>"
     *
     * Based on: https://labs.omniti.com/alexandria/trunk/OmniTI/Mail/Parser.php
     *
     * @param string                               $headerName  Header name
     * @param string                               $headerValue Header value
     * @return ParsedMimeHeader[]
     */
    public static function parseContentTypeHeader($headerName, $headerValue)
    {
        if (self::isCompositeHeaderValue($headerValue)) {
            $parsedMimeHeaders = self::parseCompositeValue($headerName, $headerValue);
        } else {
            $parsedMimeHeaders = [
                new ParsedMimeHeader($headerName, trim($headerValue))
            ];
        }

        return $parsedMimeHeaders;
    }

    private static function parseCompositeValue($headerName, $headerValue)
    {
        $parsedMimeHeaders = [];
        list($value, $remainder) = explode(';', $headerValue, 2);
        $value = trim($value);
        $parsedMimeHeaders[] = new ParsedMimeHeader($headerName, $value);
        $remainder = trim($remainder);
        while (strlen($remainder) > 0) {
            if (!preg_match('/^([a-zA-Z0-9_-]+)=(.{1})/', $remainder, $matches)) {
                break;
            }
            $name = $matches[1];
            $delimiter = $matches[2];
            $remainder = substr($remainder, strlen($name) + 1);
            // preg_match migrated from https://github.com/progmancod/BeSimpleSoap/commit/6bc8f6a467616c934b0a9792f0efece55054db97
            if (!preg_match('/([^;]+)(;\s*|\s*$)/', $remainder, $matches)) {
                break;
            }
            $value = rtrim($matches[1], ';');
            if ($delimiter == "'" || $delimiter == '"') {
                $value = trim($value, $delimiter);
            }
            $remainder = substr($remainder, strlen($matches[0]));
            $parsedMimeHeaders[] = new ParsedMimeHeader($headerName, $name, $value);
        }

        return $parsedMimeHeaders;
    }

    private static function isCompositeHeaderValue($headerValue)
    {
        return strpos($headerValue, ';');
    }
}
