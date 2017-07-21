<?php

/*
 * This file is part of the BeSimpleSoapClient.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapClient;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapBundle\Soap\SoapAttachmentList;
use BeSimple\SoapClient\Curl\Curl;
use BeSimple\SoapClient\Curl\CurlOptionsBuilder;
use BeSimple\SoapClient\Curl\CurlResponse;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapCommon\Fault\SoapFaultEnum;
use BeSimple\SoapCommon\Fault\SoapFaultParser;
use BeSimple\SoapCommon\Fault\SoapFaultPrefixEnum;
use BeSimple\SoapCommon\Fault\SoapFaultSourceGetter;
use BeSimple\SoapCommon\Mime\PartFactory;
use BeSimple\SoapCommon\SoapKernel;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapRequestFactory;
use Exception;
use SoapFault;

/**
 * Extended SoapClient that uses a a cURL wrapper for all underlying HTTP
 * requests in order to use proper authentication for all requests. This also
 * adds NTLM support. A custom WSDL downloader resolves remote xsd:includes and
 * allows caching of all remote referenced items.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapClient extends \SoapClient
{
    use SoapClientNativeMethodsTrait;

    /** @var SoapOptions */
    protected $soapOptions;
    /** @var Curl */
    private $curl;

    public function __construct(SoapClientOptions $soapClientOptions, SoapOptions $soapOptions)
    {
        $this->soapClientOptions = $soapClientOptions;
        $this->soapOptions = $soapOptions;
        $this->curl = new Curl(
            CurlOptionsBuilder::buildForSoapClient($soapClientOptions)
        );

        try {
            $wsdlPath = $this->loadWsdl(
                $this->curl,
                $soapOptions->getWsdlFile(),
                $soapOptions->getWsdlCacheType(),
                $soapClientOptions->isResolveRemoteIncludes()
            );
        } catch (Exception $e) {
            throw new SoapFault(
                SoapFaultEnum::SOAP_FAULT_SOAP_CLIENT_ERROR,
                'Unable to load WsdlPath ('.$soapOptions->getWsdlFile().') with message: '.$e->getMessage().' in file: '.$e->getFile().' (line: '.$e->getLine().')'
            );
        }

        @parent::__construct($wsdlPath, $soapClientOptions->toArray() + $soapOptions->toArray());
    }

    /**
     * @param string $functionName
     * @param array $arguments
     * @param array|null $options
     * @param SoapAttachment[] $soapAttachments
     * @param null $inputHeaders
     * @param array|null $outputHeaders
     * @return SoapResponse
     */
    public function soapCall($functionName, array $arguments, array $soapAttachments = [], array $options = null, $inputHeaders = null, array &$outputHeaders = null)
    {
        $this->setSoapAttachmentsOnRequestToStorage($soapAttachments);
        try {

            $soapResponseAsObject = parent::__soapCall($functionName, $arguments, $options, $inputHeaders, $outputHeaders);
            $soapResponse = $this->getSoapResponseFromStorage();
            $soapResponse->setResponseObject($soapResponseAsObject);

            return $soapResponse;

        } catch (SoapFault $soapFault) {
            if (SoapFaultSourceGetter::isNativeSoapFault($soapFault)) {
                $soapFault = $this->decorateNativeSoapFaultWithSoapResponseTracingData($soapFault);
            }

            throw $soapFault;
        }
    }

    /**
     * Custom request method to be able to modify the SOAP messages.
     * $oneWay parameter is not used at the moment.
     *
     * @param mixed             $request            Request object
     * @param string            $location           Location
     * @param string            $action             SOAP action
     * @param int               $version            SOAP version
     * @param SoapAttachment[]  $soapAttachments    SOAP attachments array
     *
     * @return SoapResponse
     */
    protected function performSoapRequest($request, $location, $action, $version, array $soapAttachments = [])
    {
        $soapRequest = $this->createSoapRequest($location, $action, $version, $request, $soapAttachments);

        return $this->performHttpSoapRequest($soapRequest);
    }

    protected function getSoapClientOptions()
    {
        return $this->soapClientOptions;
    }

    protected function getSoapOptions()
    {
        return $this->soapOptions;
    }

    /**
     * @param string                $location           Location
     * @param string                $action             SOAP action
     * @param int                   $version            SOAP version
     * @param string                $request            SOAP request body
     * @param SoapAttachment[]      $soapAttachments    array of SOAP attachments
     *
     * @return SoapRequest
     */
    private function createSoapRequest($location, $action, $version, $request, array $soapAttachments = [])
    {
        $soapAttachmentList = new SoapAttachmentList($soapAttachments);
        $soapRequest = SoapRequestFactory::create($location, $action, $version, $request);
        if (count($soapAttachments) > 0) {
            if ($this->soapOptions->hasAttachments() === true) {
                $soapRequest->setAttachments(PartFactory::createAttachmentParts($soapAttachments));
                $soapRequest = SoapKernel::filterRequest(
                    $soapRequest,
                    $this->getAttachmentFilters(),
                    $this->soapOptions->getAttachmentType()
                );
            } else {
                throw new Exception(
                    'Non SWA SoapClient cannot handle SOAP action '.$action.' with attachments: '.implode(', ', $soapAttachmentList->getSoapAttachmentIds())
                );
            }
        }

        return $soapRequest;
    }

    /**
     * Perform HTTP request with cURL.
     *
     * @param SoapRequest $soapRequest SoapRequest object
     * @return SoapResponse
     * @throws SoapFault
     */
    private function performHttpSoapRequest(SoapRequest $soapRequest)
    {
        $curlResponse = $this->curl->executeCurlWithCachedSession(
            $soapRequest->getLocation(),
            $soapRequest->getContent(),
            $this->getHttpHeadersBySoapVersion($soapRequest)
        );
        $soapResponseTracingData = new SoapResponseTracingData(
            $curlResponse->getHttpRequestHeaders(),
            $soapRequest->getContent(),
            $curlResponse->getResponseHeader(),
            $curlResponse->getResponseBody()
        );

        if ($curlResponse->curlStatusSuccess()) {
            $soapResponse = $this->returnSoapResponseByTracing(
                $soapRequest,
                $curlResponse,
                $soapResponseTracingData
            );
            if ($this->soapOptions->hasAttachments()) {

                return SoapKernel::filterResponse(
                    $soapResponse,
                    $this->getAttachmentFilters(),
                    $this->soapOptions->getAttachmentType()
                );
            }

            return $soapResponse;

        }
        if ($curlResponse->curlStatusFailed()) {

            if ($curlResponse->getHttpResponseStatusCode() >= 500) {
                $soapFault = SoapFaultParser::parseSoapFault(
                    $curlResponse->getResponseBody()
                );

                return $this->throwSoapFaultByTracing(
                    $soapFault->faultcode,
                    sprintf(
                        'SOAP HTTP call failed: %s with Message: %s and Code: %s',
                        $curlResponse->getCurlErrorMessage(),
                        $soapFault->getMessage(),
                        $soapFault->faultcode
                    ),
                    $soapResponseTracingData
                );
            }

            return $this->throwSoapFaultByTracing(
                SoapFaultEnum::SOAP_FAULT_HTTP.'-'.$curlResponse->getHttpResponseStatusCode(),
                $curlResponse->getCurlErrorMessage(),
                $soapResponseTracingData
            );
        }

        return $this->throwSoapFaultByTracing(
            SoapFaultEnum::SOAP_FAULT_SOAP_CLIENT_ERROR,
            'Cannot process curl response with unresolved status: ' . $curlResponse->getCurlStatus(),
            $soapResponseTracingData
        );
    }

    /**
     * @param Curl $curl
     * @param string $wsdlPath
     * @param int $wsdlCacheType
     * @param bool $resolveRemoteIncludes
     *
     * @return string
     */
    private function loadWsdl(Curl $curl, $wsdlPath, $wsdlCacheType, $resolveRemoteIncludes = true)
    {
        $wsdlDownloader = new WsdlDownloader();
        try {
            $loadedWsdlFilePath = $wsdlDownloader->getWsdlPath($curl, $wsdlPath, $wsdlCacheType, $resolveRemoteIncludes);
        } catch (Exception $e) {
            throw new SoapFault(
                SoapFaultEnum::SOAP_FAULT_WSDL,
                'Unable to load WsdlPath ('.$wsdlPath.') with message: '.$e->getMessage().' in file: '.$e->getFile().' (line: '.$e->getLine().')'
            );
        }

        return $loadedWsdlFilePath;
    }

    private function getHttpHeadersBySoapVersion(SoapRequest $soapRequest)
    {
        if ($soapRequest->getVersion() === SOAP_1_1) {

            return [
                'Content-Type: ' . $soapRequest->getContentType(),
                'SOAPAction: "' . $soapRequest->getAction() . '"',
                'Connection: ' . ($this->soapOptions->isConnectionKeepAlive() ? 'Keep-Alive' : 'close'),
            ];
        }

        return [
            'Content-Type: ' . $soapRequest->getContentType() . '; action="' . $soapRequest->getAction() . '"',
            'Connection: ' . ($this->soapOptions->isConnectionKeepAlive() ? 'Keep-Alive' : 'close'),
        ];
    }

    private function getAttachmentFilters()
    {
        $filters = [];
        if ($this->soapOptions->getAttachmentType() !== SoapOptions::SOAP_ATTACHMENTS_TYPE_BASE64) {
            $filters[] = new MimeFilter();
        }
        if ($this->soapOptions->getAttachmentType() === SoapOptions::SOAP_ATTACHMENTS_TYPE_MTOM) {
            $filters[] = new XmlMimeFilter();
        }

        return $filters;
    }

    private function returnSoapResponseByTracing(
        SoapRequest $soapRequest,
        CurlResponse $curlResponse,
        SoapResponseTracingData $soapResponseTracingData,
        array $soapAttachments = []
    ) {
        if ($this->soapClientOptions->getTrace() === true) {
            return SoapResponseFactory::createWithTracingData(
                $soapRequest,
                $curlResponse->getResponseBody(),
                $curlResponse->getHttpResponseContentType(),
                $soapResponseTracingData,
                $soapAttachments
            );
        }

        return SoapResponseFactory::create(
            $soapRequest,
            $curlResponse->getResponseBody(),
            $curlResponse->getHttpResponseContentType(),
            $soapAttachments
        );
    }

    /**
     * @param string $soapFaultCode
     * @param string $soapFaultMessage
     * @param SoapResponseTracingData $soapResponseTracingData
     * @throws SoapFault
     */
    private function throwSoapFaultByTracing($soapFaultCode, $soapFaultMessage, SoapResponseTracingData $soapResponseTracingData)
    {
        if ($this->soapClientOptions->getTrace() === true) {

            throw new SoapFaultWithTracingData(
                $soapFaultCode,
                $soapFaultMessage,
                $soapResponseTracingData
            );
        }

        throw new SoapFault(
            $soapFaultCode,
            $soapFaultMessage
        );
    }

    private function decorateNativeSoapFaultWithSoapResponseTracingData(SoapFault $nativePhpSoapFault)
    {
        return $this->throwSoapFaultByTracing(
            $nativePhpSoapFault->faultcode,
            $nativePhpSoapFault->getMessage(),
            $this->getSoapResponseTracingDataFromNativeSoapFaultOrStorage($nativePhpSoapFault)
        );
    }

    private function getSoapResponseTracingDataFromNativeSoapFaultOrStorage(SoapFault $nativePhpSoapFault)
    {
        if ($nativePhpSoapFault instanceof SoapFaultWithTracingData) {
            return $nativePhpSoapFault->getSoapResponseTracingData();
        }

        return $this->getSoapResponseTracingDataFromRequestStorage();
    }

    private function getSoapResponseTracingDataFromRequestStorage()
    {
        $lastResponseHeaders = $lastResponse = $lastRequestHeaders = $lastRequest = null;
        $soapResponse = $this->getSoapResponseFromStorage();
        if ($soapResponse instanceof SoapResponse) {
            $lastResponseHeaders = 'Content-Type: ' . $soapResponse->getContentType();
            $lastResponse = $soapResponse->getResponseContent();

            if ($soapResponse->hasRequest() === true) {
                $lastRequestHeaders = 'Content-Type: ' . $soapResponse->getRequest()->getContentType();
                $lastRequest = $soapResponse->getRequest()->getContent();
            }
        }

        return new SoapResponseTracingData(
            $lastRequestHeaders,
            $lastRequest,
            $lastResponseHeaders,
            $lastResponse
        );
    }
}
