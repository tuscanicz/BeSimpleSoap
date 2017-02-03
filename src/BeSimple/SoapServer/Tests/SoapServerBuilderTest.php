<?php

/*
 * This file is part of the BeSimpleSoapServer.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapServer\Tests;

use BeSimple\SoapClient\Tests\SoapClientBuilderTest;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use BeSimple\SoapServer\SoapOptions\SoapServerOptions;
use BeSimple\SoapServer\SoapServerBuilder;
use BeSimple\SoapServer\SoapServerOptionsBuilder;

/**
 * UnitTest for \BeSimple\SoapServer\SoapServerBuilder
 *
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyne <mail@petrbechyne.com>
 */
class SoapServerBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_LOCAL_WSDL_UK = SoapClientBuilderTest::TEST_LOCAL_WSDL_UK;
    const CACHE_DIR = __DIR__ . '/../../../../cache';

    public function testSoapOptionsCreateWithDefaults()
    {
        $defaultOptions = SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK);

        self::assertInstanceOf(SoapOptions::class, $defaultOptions);
        self::assertEquals(self::TEST_LOCAL_WSDL_UK, $defaultOptions->getWsdlFile());
    }

    public function testSoapClientOptionsCreateWithDefaults()
    {
        $defaultOptions = SoapServerOptionsBuilder::createWithDefaults(new SoapServerHandler);

        self::assertInstanceOf(SoapServerOptions::class, $defaultOptions);
        self::assertInstanceOf(SoapServerHandler::class, $defaultOptions->getHandlerInstance());
    }

    public function testSoapServerBuilderBuild()
    {
        $soapServer = $this->getSoapServerBuilder()->build(
            SoapServerOptionsBuilder::createWithDefaults(new SoapServerHandler),
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
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'testHandleRequest.message')
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
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'testHandleRequestWithSwa.message')
        );
        $response = $soapServer->handleRequest($request);

        file_put_contents(self::CACHE_DIR . '/SoapServerTestResponseFromSwaRequestWithNoAttachments.xml', $response->getContent());

        self::assertNotContains("\r\n", $response->getContent(), 'Response cannot contain CRLF line endings');
        self::assertContains('dummyServiceMethodWithAttachmentsResponse', $response->getContent());
        self::assertSame('DummyService.dummyServiceMethodWithAttachments', $response->getAction());
        self::assertFalse($response->hasAttachments(), 'Response should contain attachments');
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
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'testHandleRequestWithSwa.mimepart.message')
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
