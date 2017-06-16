<?php

namespace BeSimple;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapClient\Curl\CurlOptions;
use BeSimple\SoapClient\SoapClientBuilder;
use BeSimple\SoapClient\SoapClientOptionsBuilder;
use BeSimple\SoapClient\SoapFaultWithTracingData;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapServer\SoapServerBuilder;
use BeSimple\SoapServer\SoapServerOptionsBuilder;
use Fixtures\DummyService;
use Fixtures\DummyServiceMethodWithIncomingLargeSwaRequest;
use Fixtures\DummyServiceMethodWithOutgoingLargeSwaRequest;
use Fixtures\GenerateTestRequest;
use PHPUnit_Framework_TestCase;
use SoapHeader;

class SoapServerAndSoapClientCommunicationTest extends PHPUnit_Framework_TestCase
{
    const CACHE_DIR = __DIR__ . '/../../cache';
    const FIXTURES_DIR = __DIR__ . '/../Fixtures';
    const TEST_HTTP_URL = 'http://localhost:8000/tests';
    const TEST_HTTP_URL_INVALID = 'http://nosuchserverexists1234.com:9911';
    const LARGE_SWA_FILE = self::FIXTURES_DIR.'/large-test-file.docx';

    private $localWebServerProcess;

    public function setUp()
    {
        $this->localWebServerProcess = popen('php -S localhost:8000 > /dev/null 2>&1 &', 'r');
    }

    public function tearDown()
    {
        pclose($this->localWebServerProcess);
    }

    public function testHandleRequestWithLargeSwaResponse()
    {
        $dummyService = new DummyService();
        $classMap = new ClassMap();
        foreach ($dummyService->getClassMap() as $type => $className) {
            $classMap->add($type, $className);
        }
        $soapServerBuilder = new SoapServerBuilder();
        $soapServerOptions = SoapServerOptionsBuilder::createWithDefaults($dummyService);
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap($dummyService->getWsdlPath(), $classMap);
        $soapServer = $soapServerBuilder->build($soapServerOptions, $soapOptions);

        $request = $soapServer->createRequest(
            $dummyService->getEndpoint(),
            'DummyService.dummyServiceMethodWithOutgoingLargeSwa',
            'text/xml;charset=UTF-8',
            file_get_contents(self::FIXTURES_DIR.'/Message/Request/dummyServiceMethodWithOutgoingLargeSwa.request.message')
        );
        $response = $soapServer->handleRequest($request);

        file_put_contents(self::CACHE_DIR . '/content-type-soap-server-response.xml', $response->getContentType());
        file_put_contents(self::CACHE_DIR . '/multipart-message-soap-server-response.xml', $response->getContent());
        if ($response->hasAttachments() === true) {
            foreach ($response->getAttachments() as $attachment) {
                $fileName = preg_replace('/\<|\>/', '', $attachment->getContentId());
                file_put_contents(self::CACHE_DIR . DIRECTORY_SEPARATOR . 'attachment-server-response-' . $fileName, $attachment->getContent());

                self::assertRegExp('/filename\.(docx|html|txt)/', $fileName);
            }
        } else {
            self::fail('Response should contain attachments');
        }

        self::assertContains('dummyServiceMethodWithOutgoingLargeSwaResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethodWithOutgoingLargeSwa', $response->getAction());

        self::assertEquals(
            filesize(self::LARGE_SWA_FILE),
            filesize(self::CACHE_DIR.'/attachment-server-response-filename.docx'),
            'File cannot differ after transport from SoapClient to SoapServer'
        );
    }

    public function testSoapCallSwaWithLargeSwaResponse()
    {
        $soapClient = $this->getSoapBuilder()->buildWithSoapHeader(
            SoapClientOptionsBuilder::createWithEndpointLocation(
                self::TEST_HTTP_URL.'/SwaSenderEndpoint.php'
            ),
            SoapOptionsBuilder::createSwaWithClassMap(
                self::TEST_HTTP_URL.'/SwaSenderEndpoint.php?wsdl',
                new ClassMap([
                    'GenerateTestRequest' => GenerateTestRequest::class,
                ]),
                SoapOptions::SOAP_CACHE_TYPE_NONE
            ),
            new SoapHeader('http://schema.testcase', 'SoapHeader', [
                'user' => 'admin',
            ])
        );

        $request = new DummyServiceMethodWithOutgoingLargeSwaRequest();
        $request->dummyAttribute = 1;

        $soapResponse = $soapClient->soapCall('dummyServiceMethodWithOutgoingLargeSwa', [$request]);
        $attachments = $soapResponse->getAttachments();

        self::assertContains('</dummyServiceReturn>', $soapResponse->getResponseContent());
        self::assertTrue($soapResponse->hasAttachments(), 'Response should contain attachments');
        self::assertCount(3, $attachments);
        self::assertInstanceOf(
            SoapRequest::class,
            $soapResponse->getRequest(),
            'SoapResponse::request must be SoapRequest for SoapClient calls with enabled tracing'
        );

        file_put_contents(self::CACHE_DIR . '/multipart-message-soap-client-response.xml', $soapResponse->getContent());
        foreach ($soapResponse->getAttachments() as $attachment) {
            $fileName = preg_replace('/\<|\>/', '', $attachment->getContentId());
            file_put_contents(self::CACHE_DIR . DIRECTORY_SEPARATOR . 'attachment-client-response-' . $fileName, $attachment->getContent());

            self::assertRegExp('/filename\.(docx|html|txt)/', $fileName);
        }

        self::assertEquals(
            filesize(self::LARGE_SWA_FILE),
            filesize(self::CACHE_DIR.'/attachment-client-response-filename.docx'),
            'File cannot differ after transport from SoapClient to SoapServer'
        );
    }

    public function testSoapCallSwaWithLargeSwaResponseAndTracingOff()
    {
        $soapClient = $this->getSoapBuilder()->buildWithSoapHeader(
            new SoapClientOptions(
                SoapClientOptions::SOAP_CLIENT_TRACE_OFF,
                SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
                CurlOptions::DEFAULT_USER_AGENT,
                SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE,
                SoapClientOptions::SOAP_CLIENT_AUTHENTICATION_NONE,
                SoapClientOptions::SOAP_CLIENT_PROXY_NONE,
                self::TEST_HTTP_URL.'/SwaSenderEndpoint.php'
            ),
            SoapOptionsBuilder::createSwaWithClassMap(
                self::TEST_HTTP_URL.'/SwaSenderEndpoint.php?wsdl',
                new ClassMap([
                    'GenerateTestRequest' => GenerateTestRequest::class,
                ]),
                SoapOptions::SOAP_CACHE_TYPE_NONE
            ),
            new SoapHeader('http://schema.testcase', 'SoapHeader', [
                'user' => 'admin',
            ])
        );

        $request = new DummyServiceMethodWithOutgoingLargeSwaRequest();
        $request->dummyAttribute = 1;

        $soapResponse = $soapClient->soapCall('dummyServiceMethodWithOutgoingLargeSwa', [$request]);
        $attachments = $soapResponse->getAttachments();

        self::assertContains('</dummyServiceReturn>', $soapResponse->getResponseContent());
        self::assertTrue($soapResponse->hasAttachments(), 'Response should contain attachments');
        self::assertCount(3, $attachments);
        self::assertInstanceOf(
            SoapRequest::class,
            $soapResponse->getRequest(),
            'SoapResponse::request must be SoapRequest for SoapClient calls with disabled tracing'
        );

        file_put_contents(self::CACHE_DIR . '/multipart-message-soap-client-response.xml', $soapResponse->getContent());
        foreach ($soapResponse->getAttachments() as $attachment) {
            $fileName = preg_replace('/\<|\>/', '', $attachment->getContentId());
            file_put_contents(self::CACHE_DIR . DIRECTORY_SEPARATOR . 'attachment-client-response-' . $fileName, $attachment->getContent());

            self::assertRegExp('/filename\.(docx|html|txt)/', $fileName);
        }

        self::assertEquals(
            filesize(self::LARGE_SWA_FILE),
            filesize(self::CACHE_DIR.'/attachment-client-response-filename.docx'),
            'File cannot differ after transport from SoapClient to SoapServer'
        );
    }

    public function testSoapCallWithLargeSwaRequest()
    {
        $soapClient = $this->getSoapBuilder()->buildWithSoapHeader(
            SoapClientOptionsBuilder::createWithEndpointLocation(
                self::TEST_HTTP_URL.'/SwaReceiverEndpoint.php'
            ),
            SoapOptionsBuilder::createSwaWithClassMap(
                self::TEST_HTTP_URL.'/SwaReceiverEndpoint.php?wsdl',
                new ClassMap([
                    'DummyServiceMethodWithIncomingLargeSwaRequest' => DummyServiceMethodWithIncomingLargeSwaRequest::class,
                ]),
                SoapOptions::SOAP_CACHE_TYPE_NONE
            ),
            new SoapHeader('http://schema.testcase', 'SoapHeader', [
                'user' => 'admin',
            ])
        );

        $request = new DummyServiceMethodWithIncomingLargeSwaRequest();
        $request->dummyAttribute = 1;

        try {
            $soapResponse = $soapClient->soapCall(
                'dummyServiceMethodWithIncomingLargeSwa',
                [$request],
                [
                    new SoapAttachment('filename.txt', 'text/plain', 'plaintext file'),
                    new SoapAttachment('filename.html', 'text/html', '<html><body>Hello world</body></html>'),
                    new SoapAttachment(
                        'filename.docx',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        file_get_contents(self::LARGE_SWA_FILE)
                    ),
                ]
            );

            self::assertContains('dummyServiceMethodWithIncomingLargeSwa', $soapResponse->getRequest()->getContent());
            self::assertContains('</dummyServiceReturn>', $soapResponse->getResponseContent());
            self::assertTrue($soapResponse->getRequest()->hasAttachments(), 'Response MUST contain attachments');
            self::assertFalse($soapResponse->hasAttachments(), 'Response MUST NOT contain attachments');
            self::assertInstanceOf(SoapRequest::class, $soapResponse->getRequest());

            foreach ($soapResponse->getRequest()->getAttachments() as $attachment) {
                file_put_contents(self::CACHE_DIR . '/attachment-client-request-'.trim($attachment->getContentId(), '<>'), $attachment->getContent());
            }
            file_put_contents(self::CACHE_DIR . '/content-type-soap-client-request.xml', $soapResponse->getRequest()->getContentType());
            file_put_contents(self::CACHE_DIR.'/multipart-message-soap-client-request.xml', $soapResponse->getRequest()->getContent());

            self::assertEquals(
                filesize(self::LARGE_SWA_FILE),
                filesize(self::CACHE_DIR.'/attachment-client-request-filename.docx'),
                'File cannot differ after transport from SoapClient to SoapServer'
            );

        } catch (SoapFaultWithTracingData $e) {
            self::fail(
                'Endpoint did not return expected response: '.var_export($e->getSoapResponseTracingData()->getLastResponse(), true)
            );
        }
    }

    public function testHandleRequestWithLargeSwaRequest()
    {
        $previousSoapClientCallContentTypeCacheFile = self::CACHE_DIR.'/content-type-soap-client-request.xml';
        $previousSoapClientCallMessageBodyCacheFile = self::CACHE_DIR.'/multipart-message-soap-client-request.xml';
        if (file_exists($previousSoapClientCallContentTypeCacheFile) === false || file_exists($previousSoapClientCallMessageBodyCacheFile) === false) {
            self::fail('Cannot load data from cache: run testSoapCallWithLargeSwaRequest to get the data.');
        }
        $previousSoapClientCallContentType = file_get_contents($previousSoapClientCallContentTypeCacheFile);
        $previousSoapClientCallMessageBody = file_get_contents($previousSoapClientCallMessageBodyCacheFile);

        $dummyService = new DummyService();
        $classMap = new ClassMap();
        foreach ($dummyService->getClassMap() as $type => $className) {
            $classMap->add($type, $className);
        }
        $soapServerBuilder = new SoapServerBuilder();
        $soapServerOptions = SoapServerOptionsBuilder::createWithDefaults($dummyService);
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap($dummyService->getWsdlPath(), $classMap);
        $soapServer = $soapServerBuilder->build($soapServerOptions, $soapOptions);

        $request = $soapServer->createRequest(
            $dummyService->getEndpoint(),
            'DummyService.dummyServiceMethodWithIncomingLargeSwa',
            $previousSoapClientCallContentType,
            $previousSoapClientCallMessageBody
        );
        $response = $soapServer->handleRequest($request);

        self::assertContains('dummyServiceMethodWithIncomingLargeSwaResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethodWithIncomingLargeSwa', $response->getAction());
        self::assertEquals(
            filesize(self::LARGE_SWA_FILE),
            filesize(self::CACHE_DIR.'/attachment-server-request-filename.docx'),
            'File cannot differ after transport from SoapClient to SoapServer'
        );
    }

    public function testHandleRequestWithLargeSwaRequestAndMixedCrLf()
    {
        $soapClientCallContentType = file_get_contents(self::FIXTURES_DIR.'/Message/Request/dummyServiceMethodWithIncomingLargeSwaAndMixedCrLf.contenttypeheaders');
        $soapClientCallMessageBody = file_get_contents(self::FIXTURES_DIR.'/Message/Request/dummyServiceMethodWithIncomingLargeSwaAndMixedCrLf.request.mimepart.message');

        $dummyService = new DummyService();
        $classMap = new ClassMap();
        foreach ($dummyService->getClassMap() as $type => $className) {
            $classMap->add($type, $className);
        }
        $soapServerBuilder = new SoapServerBuilder();
        $soapServerOptions = SoapServerOptionsBuilder::createWithDefaults($dummyService);
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap($dummyService->getWsdlPath(), $classMap);
        $soapServer = $soapServerBuilder->build($soapServerOptions, $soapOptions);

        $request = $soapServer->createRequest(
            $dummyService->getEndpoint(),
            'DummyService.dummyServiceMethodWithIncomingLargeSwa',
            $soapClientCallContentType,
            $soapClientCallMessageBody
        );
        $response = $soapServer->handleRequest($request);

        self::assertContains('dummyServiceMethodWithIncomingLargeSwaResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethodWithIncomingLargeSwa', $response->getAction());
        self::assertEquals(
            filesize(self::LARGE_SWA_FILE),
            filesize(self::CACHE_DIR.'/attachment-server-request-oldfilename.docx'),
            'File cannot differ after transport from SoapClient to SoapServer'
        );
    }

    private function getSoapBuilder()
    {
        return new SoapClientBuilder();
    }

    public function getSoapServerBuilder()
    {
        return new SoapServerBuilder();
    }
}
