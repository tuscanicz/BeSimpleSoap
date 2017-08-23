<?php

namespace BeSimple\SoapCommon\Mime\Boundary;

use PHPUnit_Framework_TestCase;

class MimeBoundaryAnalyserTest extends PHPUnit_Framework_TestCase
{
    const EXPECTED_HAS_BOUNDARY = true;
    const EXPECTED_HAS_NO_BOUNDARY = false;
    const EXPECTED_IS_BOUNDARY = true;
    const EXPECTED_IS_NOT_BOUNDARY = false;

    /**
     * @dataProvider mimeMessageLinesDataProvider
     * @param string[] $mimeMessageLines
     * @param bool $expectHasBoundary
     */
    public function testHasMessageBoundary(array $mimeMessageLines, $expectHasBoundary)
    {
        $hasMessageBoundary = MimeBoundaryAnalyser::hasMessageBoundary($mimeMessageLines);

        self::assertEquals($expectHasBoundary, $hasMessageBoundary);
    }

    /**
     * @dataProvider mimeMessageLineDataProvider
     * @param string $mimeMessageLine
     * @param bool $expectIsBoundary
     */
    public function testIsMessageLineBoundary($mimeMessageLine, $expectIsBoundary)
    {
        $isMessageBoundary = MimeBoundaryAnalyser::isMessageLineBoundary($mimeMessageLine);

        self::assertEquals($expectIsBoundary, $isMessageBoundary);
    }

    public function mimeMessageLinesDataProvider()
    {
        return [
            [
                [
                    '',
                    'mesage line -- has no boundary',
                    '-- this line is a boundary',
                    '',
                    '',
                    '-- this line is also a boundary --',
                    ' -- this is not a boundary'
                ],
                self::EXPECTED_HAS_BOUNDARY
            ],
            [
                [
                    '',
                    'mesage line -- has no boundary',
                    '',
                    '',
                    ' -- this is not a boundary'
                ],
                self::EXPECTED_HAS_NO_BOUNDARY
            ]
        ];
    }

    public function mimeMessageLineDataProvider()
    {
        return [
            ['-- this line is boundary', self::EXPECTED_IS_BOUNDARY],
            ['--this line is boundary', self::EXPECTED_IS_BOUNDARY],
            ['--@ this line is not boundary', self::EXPECTED_IS_NOT_BOUNDARY],
            ['-- this line is also a boundary --', self::EXPECTED_IS_BOUNDARY],
            ['mesage line -- is not boundary', self::EXPECTED_IS_NOT_BOUNDARY],
            [' -- mesage line -- is not boundary', self::EXPECTED_IS_NOT_BOUNDARY],
        ];
    }
}
