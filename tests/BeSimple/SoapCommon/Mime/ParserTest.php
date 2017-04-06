<?php

namespace BeSimple\SoapCommon\Mime;

use Exception;
use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    const TEST_CASE_SHOULD_FAIL = true;
    const TEST_CASE_SHOULD_NOT_FAIL = false;

    /**
     * @dataProvider provideMimeMessages
     * @param string $mimeMessage
     * @param string[] $headers
     * @param bool $testCaseShouldFail
     * @param string|null $failedTestCaseFailMessage
     */
    public function testParseMimeMessage(
        $mimeMessage,
        array $headers,
        $testCaseShouldFail,
        $failedTestCaseFailMessage = null
    ) {
        if ($testCaseShouldFail === true) {
            $this->setExpectedException(Exception::class, $failedTestCaseFailMessage);
        }

        $mimeMessage = Parser::parseMimeMessage($mimeMessage, $headers);

        if ($testCaseShouldFail === false) {
            self::assertInstanceOf(MultiPart::class, $mimeMessage);
            self::assertInstanceOf(Part::class, $mimeMessage->getMainPart());
        }
    }

    public function provideMimeMessages()
    {
        return [
            'ParseRequest' => [
                $this->getMimeMessageFromFile(__DIR__.'/../../../Fixtures/Message/Request/dummyServiceMethod.message.request'),
                [],
                self::TEST_CASE_SHOULD_NOT_FAIL
            ],
            'ParseRequestOneLiner' => [
                $this->getMimeMessageFromFile(__DIR__.'/../../../Fixtures/Message/Request/dummyServiceMethod.oneliner.message.request'),
                [],
                self::TEST_CASE_SHOULD_NOT_FAIL
            ],
            'ParseSwaResponseWith2FilesAnd1BinaryFile' => [
                $this->getMimeMessageFromFile(__DIR__.'/../../../Fixtures/Message/Response/dummyServiceMethodWithOutgoingLargeSwa.response.mimepart.message'),
                [
                    'Content-Type' => 'multipart/related;'.
                        ' type="application/soap+xml"; charset=utf-8;'.
                        ' boundary=Part_13_58e3bc35f3743.58e3bc35f376f;'.
                        ' start="<part-424dbe68-e2da-450f-9a82-cc3e82742503@response.info>"'
                ],
                self::TEST_CASE_SHOULD_NOT_FAIL
            ],
            'ParseSwaResponseWith2FilesAnd1BinaryFileShouldFailWithNoHeaders' => [
                $this->getMimeMessageFromFile(__DIR__.'/../../../Fixtures/Message/Response/dummyServiceMethodWithOutgoingLargeSwa.response.mimepart.message'),
                [],
                self::TEST_CASE_SHOULD_FAIL,
                'Unable to get Content-Type boundary'
            ],
            'ParseSwaResponseWith2FilesAnd1BinaryFileShouldFailWithWrongHeaders' => [
                $this->getMimeMessageFromFile(__DIR__.'/../../../Fixtures/Message/Response/dummyServiceMethodWithOutgoingLargeSwa.response.mimepart.message'),
                [
                    'Content-Type' => 'multipart/related; type="application/soap+xml"; charset=utf-8; boundary=DOES_NOT_EXIST; start="<non-existing>"'
                ],
                self::TEST_CASE_SHOULD_FAIL,
                'cannot parse headers before hitting the first boundary'
            ],
            'ParseSwaRequestWith2Files' => [
                $this->getMimeMessageFromFile(__DIR__ . '/../../../Fixtures/Message/Request/dummyServiceMethodWithAttachments.request.mimepart.message'),
                [
                    'Content-Type' => 'multipart/related; type="application/soap+xml"; charset=utf-8; boundary=----=_Part_6_2094841787.1482231370463; start="<rootpart@soapui.org>"'
                ],
                self::TEST_CASE_SHOULD_NOT_FAIL
            ],
            'ParseSwaRequestWith2FilesShouldFailWithNoHeaders' => [
                $this->getMimeMessageFromFile(__DIR__ . '/../../../Fixtures/Message/Request/dummyServiceMethodWithAttachments.request.mimepart.message'),
                [],
                self::TEST_CASE_SHOULD_FAIL,
                'Unable to get Content-Type boundary'
            ],
        ];
    }

    private function getMimeMessageFromFile($filePath)
    {
        if (file_exists($filePath) === false) {
            self::fail('Please, update tests data provider - file not found: '.$filePath);
        }

        return file_get_contents($filePath);
    }
}
