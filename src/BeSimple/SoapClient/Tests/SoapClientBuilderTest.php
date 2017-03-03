<?php

/*
 * This file is part of the BeSimpleSoapBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapClient\Tests;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapClient\Curl\CurlOptions;
use BeSimple\SoapClient\SoapClientBuilder;
use BeSimple\SoapClient\SoapClientOptionsBuilder;
use BeSimple\SoapClient\SoapFaultWithTracingData;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use Exception;
use SoapClient;
use SoapHeader;

class SoapClientBuilderTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_DIR = __DIR__ . '/../../../../cache';
    const TEST_ENDPOINT_UK = 'http://www.webservicex.net/uklocation.asmx';
    const TEST_REMOTE_WSDL_UK = 'http://www.webservicex.net/uklocation.asmx?WSDL';
    const TEST_LOCAL_WSDL_UK = __DIR__.'/localWsdl.wsdl';
    const TEST_REMOTE_ENDPOINT_NOT_WORKING = 'http://www.nosuchserverexist.tld/doesnotexist.endpoint';
    const TEST_REMOTE_WSDL_NOT_WORKING = 'http://www.nosuchserverexist.tld/doesnotexist.endpoint?wsdl';
    const TEST_ENDPOINT_SWA = 'https://demo2815480.mockable.io/soap/testGenerator';
    const TEST_REMOTE_WSDL_SWA = 'https://demo2815480.mockable.io/soap/testGenerator?WSDL';

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

        self::assertInstanceOf(\SoapClient::class, $soapClient);
    }

    public function testConstructSoapClientWithSwaAndClassMapAndCacheDisk()
    {
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap(
            self::TEST_REMOTE_WSDL_UK,
            new ClassMap(),
            SoapOptions::SOAP_CACHE_TYPE_DISK,
            __DIR__.'/../../../../cache'
        );

        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            $soapOptions
        );

        self::assertInstanceOf(\SoapClient::class, $soapClient);
    }

    public function testConstructSoapClientWithDefaultsAndLocalWsdlFile()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::TEST_LOCAL_WSDL_UK)
        );

        self::assertInstanceOf(\SoapClient::class, $soapClient);
    }

    public function testConstructSoapClientWithSwaAndClassMapAndCacheDiskAndLocalWsdlFile()
    {
        $soapOptions = SoapOptionsBuilder::createSwaWithClassMap(
            self::TEST_LOCAL_WSDL_UK,
            new ClassMap(),
            SoapOptions::SOAP_CACHE_TYPE_DISK,
            __DIR__ .'/../../../../cache'
        );

        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            $soapOptions
        );

        self::assertInstanceOf(SoapClient::class, $soapClient);
    }

    public function testSoapCall()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::TEST_REMOTE_WSDL_UK)
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';
        $soapResponse = $soapClient->soapCall('GetUKLocationByCounty', [$getUKLocationByCountyRequest]);

        self::assertContains('GetUKLocationByCountyResult', $soapResponse->getContent());
        self::assertContains('</GetUKLocationByCountyResponse>', $soapResponse->getContent());
        self::assertEquals(self::TEST_ENDPOINT_UK, $soapResponse->getLocation());
    }

    public function testSoapCallWithCustomEndpointValid()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithEndpointLocation(self::TEST_ENDPOINT_UK),
            SoapOptionsBuilder::createWithDefaults(self::TEST_REMOTE_WSDL_UK)
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';
        $soapResponse = $soapClient->soapCall('GetUKLocationByCounty', [$getUKLocationByCountyRequest]);

        self::assertContains('Connection: close', $soapResponse->getTracingData()->getLastRequestHeaders());
        self::assertContains('County>London</', $soapResponse->getTracingData()->getLastRequest());
        self::assertContains('GetUKLocationByCountyResult', $soapResponse->getContent());
        self::assertContains('</GetUKLocationByCountyResponse>', $soapResponse->getContent());
        self::assertEquals(self::TEST_ENDPOINT_UK, $soapResponse->getLocation());
    }

    public function testSoapCallWithKeepAliveTrue()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithEndpointLocation(self::TEST_ENDPOINT_UK),
            SoapOptionsBuilder::createWithDefaultsKeepAlive(self::TEST_REMOTE_WSDL_UK)
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';
        $soapResponse = $soapClient->soapCall('GetUKLocationByCounty', [$getUKLocationByCountyRequest]);

        self::assertContains('Connection: Keep-Alive', $soapResponse->getTracingData()->getLastRequestHeaders());
        self::assertContains('County>London</', $soapResponse->getTracingData()->getLastRequest());
        self::assertContains('GetUKLocationByCountyResult', $soapResponse->getContent());
        self::assertContains('</GetUKLocationByCountyResponse>', $soapResponse->getContent());
        self::assertEquals(self::TEST_ENDPOINT_UK, $soapResponse->getLocation());
    }

    public function testSoapCallWithCustomEndpointInvalidShouldFail()
    {
        $this->setExpectedException(Exception::class, 'Could not resolve host');

        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithEndpointLocation(self::TEST_REMOTE_ENDPOINT_NOT_WORKING),
            SoapOptionsBuilder::createWithDefaults(self::TEST_REMOTE_WSDL_UK)
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';
        $soapClient->soapCall('GetUKLocationByCounty', [$getUKLocationByCountyRequest]);
    }

    public function testSoapCallWithCacheEndpointDownShouldFail()
    {
        $this->setExpectedException(Exception::class, 'Could not write WSDL cache file: Download failed with message');

        $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(
                self::TEST_REMOTE_WSDL_NOT_WORKING,
                SoapOptions::SOAP_CACHE_TYPE_DISK,
                __DIR__ .'/../../../../cache'
            )
        );
    }

    public function testSoapCallEndpointDownShouldFail()
    {
        $this->setExpectedException(Exception::class, 'Parsing WSDL: Couldn\'t load from');

        $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::TEST_REMOTE_WSDL_NOT_WORKING)
        );
    }

    public function testSoapCallNoSwaWithAttachmentMustFail()
    {
        $this->setExpectedException(Exception::class, 'Non SWA SoapClient cannot handle SOAP action');

        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::TEST_REMOTE_WSDL_UK)
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';

        $soapClient->soapCall(
            'GetUKLocationByCounty',
            [$getUKLocationByCountyRequest],
            [
                new SoapAttachment(
                    'first-file.txt',
                    'text/plain',
                    'unexpected file - no SWA - must fail'
                ),
            ]
        );
    }

    public function testSoapCallSwaWithTwoAttachments()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithTracing(),
            SoapOptionsBuilder::createSwaWithClassMap(
                self::TEST_REMOTE_WSDL_UK,
                new ClassMap(),
                SoapOptions::SOAP_CACHE_TYPE_DISK,
                __DIR__ .'/../../../../cache'
            )
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';

        try {
            $soapResponse = $soapClient->soapCall(
                'GetUKLocationByCounty',
                [$getUKLocationByCountyRequest],
                [
                    new SoapAttachment(
                        'first-file.txt',
                        'text/plain',
                        'hello world'
                    ),
                    new SoapAttachment(
                        'second-file.txt',
                        'text/plain',
                        'hello world'
                    )
                ]
            );
            $tracingData = $soapResponse->getTracingData();
        } catch (SoapFaultWithTracingData $e) {
            $tracingData = $e->getSoapResponseTracingData();
        }

        self::assertEquals(
            $this->getContentId($tracingData->getLastRequestHeaders()),
            $this->getContentId($tracingData->getLastRequest()),
            'Content ID must match in request XML and Content-Type: ...; start header'
        );
        self::assertEquals(
            $this->getMultiPartBoundary($tracingData->getLastRequestHeaders()),
            $this->getMultiPartBoundary($tracingData->getLastRequest()),
            'MultiPart boundary must match in request XML and Content-Type: ...; boundary header'
        );
        self::assertContains('boundary=Part_', $tracingData->getLastRequestHeaders(), 'Headers should link to boundary');
        self::assertContains('start="<part-', $tracingData->getLastRequestHeaders(), 'Headers should link to first MultiPart');
        self::assertContains('action="', $tracingData->getLastRequestHeaders(), 'Headers should contain SOAP action');
        self::assertEquals(
            $this->removeOneTimeData(file_get_contents(__DIR__.'/soapRequestWithTwoAttachments.request')),
            $this->removeOneTimeData($tracingData->getLastRequest()),
            'Requests must match after onetime data were removed'
        );
    }

    public function testSoapCallSwaWithNoAttachments()
    {
        $soapClient = $this->getSoapBuilder()->build(
            SoapClientOptionsBuilder::createWithTracing(),
            SoapOptionsBuilder::createSwaWithClassMap(
                self::TEST_REMOTE_WSDL_UK,
                new ClassMap(),
                SoapOptions::SOAP_CACHE_TYPE_DISK,
                __DIR__ .'/../../../../cache'
            )
        );
        $getUKLocationByCountyRequest = new GetUKLocationByCounty();
        $getUKLocationByCountyRequest->County = 'London';

        try {
            $soapResponse = $soapClient->soapCall(
                'GetUKLocationByCounty',
                [$getUKLocationByCountyRequest]
            );
            $tracingData = $soapResponse->getTracingData();
        } catch (SoapFaultWithTracingData $e) {
            $tracingData = $e->getSoapResponseTracingData();
        }

        self::assertNotContains('boundary=Part_', $tracingData->getLastRequestHeaders(), 'Headers should link to boundary');
        self::assertNotContains('start="<part-', $tracingData->getLastRequestHeaders(), 'Headers should link to first MultiPart');
        self::assertContains('action="', $tracingData->getLastRequestHeaders(), 'Headers should contain SOAP action');
        self::assertEquals(
            file_get_contents(__DIR__.'/soapRequestWithNoAttachments.request'),
            $tracingData->getLastRequest(),
            'Requests must match'
        );
    }

    /**
     * @see This test needs a working SWA endpoint. Examine Tests/Mock directory for details
     */
    public function testSoapCallSwaWithAttachmentsOnResponse()
    {
        $soapClient = $this->getSoapBuilder()->buildWithSoapHeader(
            SoapClientOptionsBuilder::createWithTracing(),
            SoapOptionsBuilder::createSwaWithClassMapV11(
                self::TEST_REMOTE_WSDL_SWA,
                new ClassMap([
                    'GenerateTestRequest' => GenerateTestRequest::class,
                ]),
                SoapOptions::SOAP_CACHE_TYPE_DISK,
                __DIR__ . '/../../../../cache'
            ),
            new SoapHeader('http://schema.testcase', 'SoapHeader', [
                'user' => 'admin',
            ])
        );
        $generateTestRequest = new GenerateTestRequest();
        $generateTestRequest->salutation = 'World';

        $soapResponse = $soapClient->soapCall('generateTest', [$generateTestRequest]);
        $attachments = $soapResponse->getAttachments();

        self::assertContains('</generateTestReturn>', $soapResponse->getResponseContent());
        self::assertTrue($soapResponse->hasAttachments());
        self::assertCount(1, $attachments);

        $firstAttachment = reset($attachments);

        self::assertEquals('text/plain', $firstAttachment->getHeader('Content-Type'));

        file_put_contents(self::CACHE_DIR . '/testSoapCallSwaWithAttachmentsOnResponse.xml', $soapResponse->getContent());
        file_put_contents(self::CACHE_DIR . '/testSoapCallSwaWithAttachmentsOnResponse.txt', $firstAttachment->getContent());
    }

    private function getSoapBuilder()
    {
        return new SoapClientBuilder();
    }

    public function removeOneTimeData($string)
    {
        $contentId = $this->getContentId($string);
        $multiPartBoundary = $this->getMultiPartBoundary($string);

        return str_replace(
            $contentId,
            '{content-id-placeholder}',
            str_replace(
                $multiPartBoundary,
                '{multipart-boundary-placeholder}',
                $string
            )
        );
    }

    private function getMultiPartBoundary($string)
    {
        $realMultiParts = null;
        preg_match('/Part\_[0-9]{2}\_[a-zA-Z0-9]{13}\.[a-zA-Z0-9]{13}/', $string, $realMultiParts);
        if (count($realMultiParts) > 0) {
            return $realMultiParts[0];
        }

        throw new Exception('Could not find real MultiPart boundary');
    }

    private function getContentId($string)
    {
        $realContentIds = null;
        preg_match('/part\-[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}\@response\.info/', $string, $realContentIds);
        if (count($realContentIds) > 0) {
            return $realContentIds[0];
        }

        throw new Exception('Could not find real contentId');
    }
}
