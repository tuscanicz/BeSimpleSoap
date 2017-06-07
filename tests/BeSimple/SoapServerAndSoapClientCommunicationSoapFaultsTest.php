<?php

namespace BeSimple;

use BeSimple\SoapClient\SoapClientBuilder;
use BeSimple\SoapClient\SoapClientOptionsBuilder;
use BeSimple\SoapClient\SoapFaultWithTracingData;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use BeSimple\SoapServer\SoapServerBuilder;
use Fixtures\GenerateTestRequest;
use PHPUnit_Framework_TestCase;
use SoapFault;
use SoapHeader;

class SoapServerAndSoapClientCommunicationSoapFaultsTest extends PHPUnit_Framework_TestCase
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

    public function testSoapCallSwaWithLargeSwaResponseWithSoapFault()
    {
        $soapClient = $this->getSoapClientBuilder()->buildWithSoapHeader(
            SoapClientOptionsBuilder::createWithEndpointLocation(
                self::TEST_HTTP_URL.'/SwaSenderSoapFaultEndpoint.php'
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

        $this->setExpectedException(SoapFault::class);

        try {
            $soapClient->soapCall('dummyServiceMethodWithOutgoingLargeSwa', []);
        } catch (SoapFault $e) {
            self::assertInstanceOf(
                SoapFaultWithTracingData::class,
                $e
            );
            /** @var SoapFaultWithTracingData $e */
            self::assertEquals(
                '911',
                $e->faultcode
            );
            self::assertContains(
                'with HTTP response code 500 with Message: This is a dummy SoapFault. and Code: 911',
                $e->getMessage()
            );
            self::assertContains(
                '<faultcode>911</faultcode>',
                $e->getSoapResponseTracingData()->getLastResponse()
            );
            self::assertContains(
                '<request/>',
                $e->getSoapResponseTracingData()->getLastRequest()
            );
            self::assertContains(
                'Content-Type: application/soap+xml; charset=utf-8; action="DummyService.dummyServiceMethodWithOutgoingLargeSwa"',
                $e->getSoapResponseTracingData()->getLastRequestHeaders()
            );

            throw $e;
        }

        self::fail('Expected SoapFault was not thrown');
    }

    public function testSoapCallSwaWithLargeSwaResponseWithNoResponseFromEndpoint()
    {
        $soapClient = $this->getSoapClientBuilder()->buildWithSoapHeader(
            SoapClientOptionsBuilder::createWithEndpointLocation(
                self::TEST_HTTP_URL.'/NoSuchEndpointExists'
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

        $this->setExpectedException(SoapFault::class);

        try {
            $soapClient->soapCall('dummyServiceMethodWithOutgoingLargeSwa', []);
        } catch (SoapFault $e) {
            self::assertInstanceOf(
                SoapFaultWithTracingData::class,
                $e
            );
            /** @var SoapFaultWithTracingData $e */
            self::assertEquals(
                'be-http-404',
                $e->faultcode
            );
            self::assertContains(
                'with HTTP response code 404',
                $e->getMessage()
            );
            self::assertContains(
                'not found',
                $e->getSoapResponseTracingData()->getLastResponse()
            );
            self::assertContains(
                '404 Not Found',
                $e->getSoapResponseTracingData()->getLastResponseHeaders()
            );
            self::assertContains(
                '<request/>',
                $e->getSoapResponseTracingData()->getLastRequest()
            );
            self::assertContains(
                'Content-Type: application/soap+xml; charset=utf-8; action="DummyService.dummyServiceMethodWithOutgoingLargeSwa"',
                $e->getSoapResponseTracingData()->getLastRequestHeaders()
            );

            throw $e;
        }

        self::fail('Expected SoapFault was not thrown');
    }

    public function testSoapCallSwaWithLargeSwaResponseWithNoResponseFromEndpointHost()
    {
        $soapClient = $this->getSoapClientBuilder()->buildWithSoapHeader(
            SoapClientOptionsBuilder::createWithEndpointLocation(
                self::TEST_HTTP_URL_INVALID.'/NoSuchEndpointExists'
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

        $this->setExpectedException(SoapFault::class);

        try {
            $soapClient->soapCall('dummyServiceMethodWithOutgoingLargeSwa', []);
        } catch (SoapFault $e) {
            self::assertInstanceOf(
                SoapFaultWithTracingData::class,
                $e
            );
            /** @var SoapFaultWithTracingData $e */
            self::assertEquals(
                'be-http-0',
                $e->faultcode
            );
            self::assertContains(
                't resolve host',
                $e->getMessage()
            );
            self::assertNull(
                $e->getSoapResponseTracingData()->getLastResponseHeaders()
            );
            self::assertNull(
                $e->getSoapResponseTracingData()->getLastResponse()
            );
            self::assertContains(
                '<request/>',
                $e->getSoapResponseTracingData()->getLastRequest()
            );
            self::assertNull(
                $e->getSoapResponseTracingData()->getLastRequestHeaders()
            );

            throw $e;
        }

        self::fail('Expected SoapFault was not thrown');
    }

    private function getSoapClientBuilder()
    {
        return new SoapClientBuilder();
    }

    public function getSoapServerBuilder()
    {
        return new SoapServerBuilder();
    }
}
