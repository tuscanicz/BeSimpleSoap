<?php

namespace BeSimple\SoapCommon\Mime\Parser;

use BeSimple\SoapCommon\Mime\MultiPart;
use Exception;
use PHPUnit_Framework_TestCase;

class ParsedPartsGetterTest extends PHPUnit_Framework_TestCase
{
    const TEST_CASE_SHOULD_FAIL = true;
    const TEST_CASE_SHOULD_NOT_FAIL = false;

    /**
     * @dataProvider provideMimeMessageLines
     * @param MultiPart $multiPart
     * @param array $mimeMessageLines
     * @param bool $hasHttpRequestHeaders
     * @param bool $testCaseShouldFail
     * @param string|null $failedTestCaseFailMessage
     */
    public function testGetPartsFromMimeMessageLines(
        MultiPart $multiPart,
        array $mimeMessageLines,
        $hasHttpRequestHeaders,
        $testCaseShouldFail,
        $failedTestCaseFailMessage = null
    ) {
        if ($testCaseShouldFail === true) {
            $this->setExpectedException(Exception::class, $failedTestCaseFailMessage);
        }
        $parsedPartsList = ParsedPartsGetter::getPartsFromMimeMessageLines(
            $multiPart,
            $mimeMessageLines,
            $hasHttpRequestHeaders
        );

        if ($testCaseShouldFail === false) {
            self::assertInstanceOf(ParsedPartList::class, $parsedPartsList);
            self::assertGreaterThanOrEqual(3, $parsedPartsList->getPartCount());
            self::assertTrue($parsedPartsList->hasExactlyOneMainPart());
        }
    }

    public function provideMimeMessageLines()
    {
        $mimePartWithHeadersForSwaResponse = new MultiPart();
        $mimePartWithHeadersForSwaResponse->setHeader('Content-Type', 'boundary', 'Part_13_58e3bc35f3743.58e3bc35f376f');
        $mimePartWithHeadersForSwaResponse->setHeader('Content-Type', 'start', '<part-424dbe68-e2da-450f-9a82-cc3e82742503@response.info>');

        $mimePartWithWrongHeadersForSwaResponse = new MultiPart();
        $mimePartWithWrongHeadersForSwaResponse->setHeader('Content-Type', 'boundary', 'non-existing');
        $mimePartWithWrongHeadersForSwaResponse->setHeader('Content-Type', 'start', '<does-not-exist>');

        $mimePartWithHeadersForSwaRequest = new MultiPart();
        $mimePartWithHeadersForSwaRequest->setHeader('Content-Type', 'boundary', '----=_Part_6_2094841787.1482231370463');
        $mimePartWithHeadersForSwaRequest->setHeader('Content-Type', 'start', '<rootpart@soapui.org>');

        return [
            'ParseSwaResponseWith2FilesAnd1BinaryFile' => [
                $mimePartWithHeadersForSwaResponse,
                $this->getMessageLinesFromMimeMessage(__DIR__.'/../../../../Fixtures/Message/Response/dummyServiceMethodWithOutgoingLargeSwa.response.mimepart.message'),
                ParsedPartsGetter::HAS_HTTP_REQUEST_HEADERS,
                self::TEST_CASE_SHOULD_NOT_FAIL
            ],
            'ParseSwaResponseWith2FilesAnd1BinaryFileShouldFailWithNoHeaders' => [
                new MultiPart(),
                $this->getMessageLinesFromMimeMessage(__DIR__.'/../../../../Fixtures/Message/Response/dummyServiceMethodWithOutgoingLargeSwa.response.mimepart.message'),
                ParsedPartsGetter::HAS_NO_HTTP_REQUEST_HEADERS,
                self::TEST_CASE_SHOULD_FAIL,
                'Unable to get Content-Type boundary'
            ],
            'ParseSwaResponseWith2FilesAnd1BinaryFileShouldFailWithWrongHeaders' => [
                $mimePartWithWrongHeadersForSwaResponse,
                $this->getMessageLinesFromMimeMessage(__DIR__.'/../../../../Fixtures/Message/Response/dummyServiceMethodWithOutgoingLargeSwa.response.mimepart.message'),
                ParsedPartsGetter::HAS_HTTP_REQUEST_HEADERS,
                self::TEST_CASE_SHOULD_FAIL,
                'cannot parse headers before hitting the first boundary'
            ],
            'ParseSwaRequestWith2Files' => [
                $mimePartWithHeadersForSwaRequest,
                $this->getMessageLinesFromMimeMessage(__DIR__ . '/../../../../Fixtures/Message/Request/dummyServiceMethodWithAttachments.request.mimepart.message'),
                ParsedPartsGetter::HAS_HTTP_REQUEST_HEADERS,
                self::TEST_CASE_SHOULD_NOT_FAIL
            ],
            'ParseSwaRequestWith2FilesShouldFailWithNoHeaders' => [
                new MultiPart(),
                $this->getMessageLinesFromMimeMessage(__DIR__ . '/../../../../Fixtures/Message/Request/dummyServiceMethodWithAttachments.request.mimepart.message'),
                ParsedPartsGetter::HAS_NO_HTTP_REQUEST_HEADERS,
                self::TEST_CASE_SHOULD_FAIL,
                'Unable to get Content-Type boundary'
            ],
        ];
    }

    private function getMessageLinesFromMimeMessage($filePath)
    {
        if (file_exists($filePath) === false) {
            self::fail('Please, update tests data provider - file not found: '.$filePath);
        }

        return explode("\n", file_get_contents($filePath));
    }
}
