<?php

namespace BeSimple\SoapServer;

use BeSimple\SoapClient\SoapClientBuilderTest;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use BeSimple\SoapServer\SoapOptions\SoapServerOptions;
use Fixtures\SoapServerHandler;
use PHPUnit_Framework_TestCase;

class SoapServerBuilderTest extends PHPUnit_Framework_TestCase
{
    const CACHE_DIR = __DIR__ . '/../../../cache';
    const FIXTURES_DIR = __DIR__ . '/../../Fixtures';
    const TEST_LOCAL_WSDL_UK = SoapClientBuilderTest::TEST_LOCAL_WSDL_UK;

    public function testSoapOptionsCreateWithDefaults()
    {
        $defaultOptions = SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK);

        self::assertInstanceOf(SoapOptions::class, $defaultOptions);
        self::assertEquals(self::TEST_LOCAL_WSDL_UK, $defaultOptions->getWsdlFile());
    }

    public function testSoapServerOptionsCreateWithDefaults()
    {
        $defaultOptions = SoapServerOptionsBuilder::createWithDefaults(new SoapServerHandler());

        self::assertInstanceOf(SoapServerOptions::class, $defaultOptions);
        self::assertInstanceOf(SoapServerHandler::class, $defaultOptions->getHandlerInstance());
    }

    public function testSoapServerBuilderBuild()
    {
        $soapServer = $this->getSoapServerBuilder()->build(
            SoapServerOptionsBuilder::createWithDefaults(new SoapServerHandler()),
            SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK)
        );

        self::assertInstanceOf(SoapServer::class, $soapServer);
    }

    public function getSoapServerBuilder()
    {
        return new SoapServerBuilder();
    }
}
