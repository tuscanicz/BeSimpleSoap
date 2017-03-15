<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapClient\Curl\CurlOptions;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use PHPUnit_Framework_TestCase;
use SoapClient;

class SoapClientBuilderTest extends PHPUnit_Framework_TestCase
{
    const CACHE_DIR = __DIR__ . '/../../../cache';
    const FIXTURES_DIR = __DIR__ . '/../../Fixtures';
    const TEST_REMOTE_WSDL_UK = 'http://www.webservicex.net/uklocation.asmx?WSDL';
    const TEST_LOCAL_WSDL_UK = self::FIXTURES_DIR.'/localWsdl.wsdl';

    public function testSoapOptionsCreateWithDefaults()
    {
        $defaultOptions = SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK);

        self::assertInstanceOf(SoapOptions::class, $defaultOptions);
        self::assertEquals(self::TEST_LOCAL_WSDL_UK, $defaultOptions->getWsdlFile());
    }

    public function testSoapClientOptionsCreateWithDefaults()
    {
        $defaultOptions = SoapClientOptionsBuilder::createWithDefaults();

        self::assertInstanceOf(SoapClientOptions::class, $defaultOptions);
        self::assertEquals(CurlOptions::DEFAULT_USER_AGENT, $defaultOptions->getUserAgent());
    }

    public function testConstructSoapClientWithDefaults()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::TEST_REMOTE_WSDL_UK)
        );

        self::assertInstanceOf(SoapClient::class, $soapClient);
    }

    public function testConstructSoapClientWithSwaAndClassMapAndCacheDisk()
    {
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap(
            self::TEST_REMOTE_WSDL_UK,
            new ClassMap(),
            SoapOptions::SOAP_CACHE_TYPE_DISK,
            self::CACHE_DIR
        );

        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            $soapOptions
        );

        self::assertInstanceOf(SoapClient::class, $soapClient);
    }

    public function testConstructSoapClientWithDefaultsAndLocalWsdlFile()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK)
        );

        self::assertInstanceOf(SoapClient::class, $soapClient);
    }

    public function testConstructSoapClientWithSwaAndClassMapAndCacheDiskAndLocalWsdlFile()
    {
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap(
            self::TEST_LOCAL_WSDL_UK,
            new ClassMap(),
            SoapOptions::SOAP_CACHE_TYPE_DISK,
            self::CACHE_DIR
        );

        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            $soapOptions
        );

        self::assertInstanceOf(SoapClient::class, $soapClient);
    }

    private function getSoapBuilder()
    {
        return new SoapClientBuilder();
    }
}
