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

/**
 * Mime part base class.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
abstract class PartHeader
{
    /** @var string[] array of headers with lower-cased keys */
    private $headers;
    /** @var string[] array of lower-cased keys and their original variants */
    private $headersOriginalKeys;

    /**
     * Add a new header to the mime part.
     *
     * @param string $name     Header name
     * @param string $value    Header value
     * @param string $subValue Is sub value?
     *
     * @return void
     */
    public function setHeader($name, $value, $subValue = null)
    {
        $lowerCaseName = mb_strtolower($name);
        $this->headersOriginalKeys[$lowerCaseName] = $name;
        if (isset($this->headers[$lowerCaseName]) && !is_null($subValue)) {
            if (!is_array($this->headers[$lowerCaseName])) {
                $this->headers[$lowerCaseName] = [
                    '@'    => $this->headers[$lowerCaseName],
                    $value => $subValue,
                ];
            } else {
                $this->headers[$lowerCaseName][$value] = $subValue;
            }
        } elseif (isset($this->headers[$lowerCaseName]) && is_array($this->headers[$lowerCaseName]) && isset($this->headers[$lowerCaseName]['@'])) {
            $this->headers[$lowerCaseName]['@'] = $value;
        } else {
            $this->headers[$lowerCaseName] = $value;
        }
    }

    /**
     * Get given mime header.
     *
     * @param string $name     Header name
     * @param string $subValue Sub value name
     *
     * @return mixed|array(mixed)
     */
    public function getHeader($name, $subValue = null)
    {
        $lowerCaseName = mb_strtolower($name);
        if (isset($this->headers[$lowerCaseName])) {
            if (!is_null($subValue)) {
                if (is_array($this->headers[$lowerCaseName]) && isset($this->headers[$lowerCaseName][$subValue])) {
                    return $this->headers[$lowerCaseName][$subValue];
                } else {
                    return null;
                }
            } elseif (is_array($this->headers[$lowerCaseName]) && isset($this->headers[$lowerCaseName]['@'])) {
                return $this->headers[$lowerCaseName]['@'];
            } else {
                return $this->headers[$lowerCaseName];
            }
        }

        return null;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get string array with MIME headers for usage in HTTP header (with CURL).
     * Only 'Content-Type' and 'Content-Description' headers are returned.
     *
     * @return string[]
     */
    public function getHeadersForHttp()
    {
        $allowedHeadersLowerCase = [
            'content-type',
            'content-description',
        ];
        $headers = [];
        foreach ($this->headers as $fieldName => $value) {
            if (in_array($fieldName, $allowedHeadersLowerCase)) {
                $fieldValue = $this->generateHeaderFieldValue($value);
                // for http only ISO-8859-1
                $headers[] = $this->headersOriginalKeys[$fieldName] . ': '. iconv('utf-8', 'ISO-8859-1//TRANSLIT', $fieldValue);
            }
        }

        return $headers;
    }

    /**
     * Generate headers.
     *
     * @return string
     */
    protected function generateHeaders()
    {
        $headers = '';
        foreach ($this->headers as $fieldName => $value) {
            $fieldValue = $this->generateHeaderFieldValue($value);
            $headers .= $this->headersOriginalKeys[$fieldName] . ': ' . $fieldValue . "\n";
        }

        return $headers;
    }

    /**
     * Generates a header field value from the given value paramater.
     *
     * @param string[]|string $value Header value
     * @return string
     */
    protected function generateHeaderFieldValue($value)
    {
        $fieldValue = '';
        if (is_array($value) === true) {
            if (isset($value['@'])) {
                $fieldValue .= $value['@'];
            }
            foreach ($value as $subName => $subValue) {
                if ($subName !== '@') {
                    $fieldValue .= '; ' . $subName . '=' . $this->quoteValueString($subValue);
                }
            }
        } else {
            $fieldValue .= $value;
        }

        return $fieldValue;
    }

    /**
     * Quote string with '"' if it contains one of the special characters:
     * "(" / ")" / "<" / ">" / "@" / "," / ";" / ":" / "\" / <"> / "/" / "[" / "]" / "?" / "="
     *
     * @param string $string String to quote
     *
     * @return string
     */
    private function quoteValueString($string)
    {
        if (preg_match('~[()<>@,;:\\"/\[\]?=]~', $string)) {
            return '"' . $string . '"';
        }

        return $string;
    }
}
