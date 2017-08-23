<?php

namespace BeSimple\SoapCommon\Mime\Boundary;

class MimeBoundaryAnalyser
{
    /**
     * @param string[] $mimeMessageLines
     * @return bool
     */
    public static function hasMessageBoundary(array $mimeMessageLines)
    {
        foreach ($mimeMessageLines as $mimeMessageLine) {
            if (self::isMessageLineBoundary($mimeMessageLine)) {

                return true;
            }
        }

        return false;
    }

    /**
     * @todo: This method is not reliable at all
     * @param string $mimeMessageLine
     * @return bool
     */
    public static function isMessageLineBoundary($mimeMessageLine)
    {
        return preg_match('/^--[0-9A-Za-z\s\'\/\+\_\,\-\.\:\=\?]+/', $mimeMessageLine) === 1;
    }

    /**
     * @param string $mimeMessageLine
     * @param string $mimeTypeBoundary
     * @return bool
     */
    public static function isMessageLineMiddleBoundary($mimeMessageLine, $mimeTypeBoundary)
    {
        return strcmp(trim($mimeMessageLine), '--'.$mimeTypeBoundary) === 0;
    }

    /**
     * @param string $mimeMessageLine
     * @param string $mimeTypeBoundary
     * @return bool
     */
    public static function isMessageLineLastBoundary($mimeMessageLine, $mimeTypeBoundary)
    {
        return strcmp(trim($mimeMessageLine), '--'.$mimeTypeBoundary.'--') === 0;
    }
}
