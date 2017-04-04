<?php

namespace BeSimple\SoapServer;

use BeSimple\SoapClient\SoapClientBuilderTest;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use Fixtures\DummyService;
use Fixtures\SoapServerHandler;
use PHPUnit_Framework_TestCase;

class SoapServerTest extends PHPUnit_Framework_TestCase
{
    const CACHE_DIR = __DIR__ . '/../../../cache';
    const FIXTURES_DIR = __DIR__ . '/../../Fixtures';
    const TEST_LOCAL_WSDL_UK = SoapClientBuilderTest::TEST_LOCAL_WSDL_UK;

    public function testCreateRequest()
    {
        $soapServer = $this->getSoapServerBuilder()->build(
            SoapServerOptionsBuilder::createWithDefaults(new SoapServerHandler()),
            SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK)
        );
        $soapRequest = $soapServer->createRequest('request-url', 'soap-action', 'content/type', 'request-content');

        self::assertEquals('content/type', $soapRequest->getContentType());
        self::assertEquals('soap-action', $soapRequest->getAction());
        self::assertEquals('request-content', $soapRequest->getContent());
        self::assertFalse($soapRequest->hasAttachments());
        self::assertNull($soapRequest->getAttachments());
    }

    public function testHandleRequest()
    {
        $dummyService = new DummyService();
        $classMap = new ClassMap();
        foreach ($dummyService->getClassMap() as $type => $className) {
            $classMap->add($type, $className);
        }
        $soapServerBuilder = new SoapServerBuilder();
        $soapServerOptions = SoapServerOptionsBuilder::createWithDefaults($dummyService);
        $soapOptions = SoapOptionsBuilder::createWithClassMap($dummyService->getWsdlPath(), $classMap);
        $soapServer = $soapServerBuilder->build($soapServerOptions, $soapOptions);

        $request = $soapServer->createRequest(
            $dummyService->getEndpoint(),
            'DummyService.dummyServiceMethod',
            'text/xml;charset=UTF-8',
            file_get_contents(self::FIXTURES_DIR.'/Message/Request/dummyServiceMethod.message.request')
        );
        $response = $soapServer->handleRequest($request);

        file_put_contents(self::CACHE_DIR . '/SoapServerTestResponse.xml', $response->getContent());

        self::assertNotContains("\r\n", $response->getContent(), 'Response cannot contain CRLF line endings');
        self::assertContains('dummyServiceMethodResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethod', $response->getAction());
        self::assertFalse($response->hasAttachments(), 'Response should not contain attachments');
    }

    public function testHandleRequestWithSwa()
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
            'DummyService.dummyServiceMethodWithAttachments',
            'text/xml;charset=UTF-8',
            file_get_contents(self::FIXTURES_DIR.'/Message/Request/dummyServiceMethodWithAttachments.request.message')
        );
        $response = $soapServer->handleRequest($request);

        file_put_contents(self::CACHE_DIR . '/SoapServerTestResponseFromSwaRequestWithNoAttachments.xml', $response->getContent());

        self::assertNotContains("\r\n", $response->getContent(), 'Response cannot contain CRLF line endings');
        self::assertContains('dummyServiceMethodWithAttachmentsResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethodWithAttachments', $response->getAction());
        self::assertFalse($response->hasAttachments(), 'Response should not contain attachments');
    }

    public function testHandleRequestWithSwaResponse()
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
            'DummyService.dummyServiceMethodWithAttachments',
            'multipart/related; type="text/xml"; start="<rootpart@soapui.org>"; boundary="----=_Part_6_2094841787.1482231370463"',
            file_get_contents(self::FIXTURES_DIR.'/Message/Request/dummyServiceMethodWithAttachments.request.mimepart.message')
        );
        $response = $soapServer->handleRequest($request);

        file_put_contents(self::CACHE_DIR . '/SoapServerTestSwaResponseWithAttachments.xml', $response->getContent());

        self::assertNotContains("\r\n", $response->getContent(), 'Response cannot contain CRLF line endings');
        self::assertContains('dummyServiceMethodWithAttachmentsResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethodWithAttachments', $response->getAction());
        self::assertTrue($response->hasAttachments(), 'Response should contain attachments');
        self::assertCount(2, $response->getAttachments());
    }

    public function getSoapServerBuilder()
    {
        return new SoapServerBuilder();
    }
}
