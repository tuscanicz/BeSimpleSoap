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
    protected $soapClientOptions;
    protected $soapOptions;
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
                $soapOptions->getWsdlCacheType()
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
     * Avoid using __call directly, it's deprecated even in \SoapClient.
     *
     * @deprecated
     */
    public function __call($function_name, $arguments)
    {
        throw new Exception(
            'The __call method is deprecated. Use __soapCall/soapCall  instead.'
        );
    }

    /**
     * Using __soapCall returns only response string, use soapCall instead.
     *
     * @param string $function_name
     * @param array $arguments
     * @param array|null $options
     * @param null $input_headers
     * @param array|null $output_headers
     * @return string
     */
    public function __soapCall($function_name, array $arguments, array $options = null, $input_headers = null, array &$output_headers = null)
    {
        return $this->soapCall($function_name, $arguments, $options, $input_headers, $output_headers)->getContent();
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
        ob_start();
        parent::__soapCall($functionName, $arguments, $options, $inputHeaders, $outputHeaders);
        $nativeSoapClientRequest = $this->mapNativeDataJsonToDto(ob_get_clean());

        return $this->performSoapRequest(
            $nativeSoapClientRequest->request,
            $nativeSoapClientRequest->location,
            $nativeSoapClientRequest->action,
            $nativeSoapClientRequest->version,
            $soapAttachments
        );
    }

    /**
     * This is not performing any HTTP requests, but it is getting data from SoapClient that are needed for this Client
     *
     * @param string $request  Request string
     * @param string $location Location
     * @param string $action   SOAP action
     * @param int    $version  SOAP version
     * @param int    $oneWay   0|1
     *
     * @return string
     */
    public function __doRequest($request, $location, $action, $version, $oneWay = 0)
    {
        $soapClientNativeDataTransferObject = new SoapClientNativeDataTransferObject(
            $request,
            $location,
            $action,
            $version
        );
        echo json_encode($soapClientNativeDataTransferObject);

        return $request;
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
    public function performSoapRequest($request, $location, $action, $version, array $soapAttachments = [])
    {
        $soapRequest = $this->createSoapRequest($location, $action, $version, $request, $soapAttachments);

        return $this->performHttpSoapRequest($soapRequest);
    }

    /** @deprecated */
    public function __getLastRequestHeaders()
    {
        $this->checkTracing();

        throw new Exception(
            'The __getLastRequestHeaders method is now deprecated. Use callSoapRequest instead and get the tracing information from SoapResponseTracingData.'
        );
    }

    /** @deprecated */
    public function __getLastRequest()
    {
        $this->checkTracing();

        throw new Exception(
            'The __getLastRequest method is now deprecated. Use callSoapRequest instead and get the tracing information from SoapResponseTracingData.'
        );
    }

    /** @deprecated */
    public function __getLastResponseHeaders()
    {
        $this->checkTracing();

        throw new Exception(
            'The __getLastResponseHeaders method is now deprecated. Use callSoapRequest instead and get the tracing information from SoapResponseTracingData.'
        );
    }

    /** @deprecated */
    public function __getLastResponse()
    {
        $this->checkTracing();

        throw new Exception(
            'The __getLastResponse method is now deprecated. Use callSoapRequest instead and get the tracing information from SoapResponseTracingData.'
        );
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
        if ($soapRequest->getVersion() === SOAP_1_1) {
            $headers = [
                'Content-Type:' . $soapRequest->getContentType(),
                'SOAPAction: "' . $soapRequest->getAction() . '"',
            ];
        } else {
            $headers = [
                'Content-Type:' . $soapRequest->getContentType() . '; action="' . $soapRequest->getAction() . '"',
            ];
        }
        $curlResponse = $this->curl->executeCurlWithCachedSession(
            $soapRequest->getLocation(),
            $soapRequest->getContent(),
            $headers
        );
        $soapResponseTracingData = new SoapResponseTracingData(
            $curlResponse->getHttpRequestHeaders(),
            $soapRequest->getContent(),
            $curlResponse->getResponseHeader(),
            $curlResponse->getResponseBody()
        );

        if ($curlResponse->curlStatusSuccess()) {
            $soapResponse = $this->returnSoapResponseByTracing(
                $this->soapClientOptions->getTrace(),
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

            } else {

                return $soapResponse;
            }
        } else if ($curlResponse->curlStatusFailed()) {

            return $this->throwSoapFaultByTracing(
                $this->soapClientOptions->getTrace(),
                SoapFaultEnum::SOAP_FAULT_HTTP.'-'.$curlResponse->getHttpResponseStatusCode(),
                $curlResponse->getCurlErrorMessage(),
                $soapResponseTracingData
            );
        } else {

            return $this->throwSoapFaultByTracing(
                $this->soapClientOptions->getTrace(),
                SoapFaultEnum::SOAP_FAULT_SOAP_CLIENT_ERROR,
                'Cannot process curl response with unresolved status: ' . $curlResponse->getCurlStatus(),
                $soapResponseTracingData
            );
        }
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

    private function mapNativeDataJsonToDto($nativeDataJson)
    {
        $nativeData = json_decode($nativeDataJson);

        return new SoapClientNativeDataTransferObject(
            $nativeData->request,
            $nativeData->location,
            $nativeData->action,
            $nativeData->version
        );
    }

    private function returnSoapResponseByTracing(
        $isTracingEnabled,
        SoapRequest $soapRequest,
        CurlResponse $curlResponse,
        SoapResponseTracingData $soapResponseTracingData,
        array $soapAttachments = []
    ) {
        if ($isTracingEnabled === true) {

            return SoapResponseFactory::createWithTracingData(
                $curlResponse->getResponseBody(),
                $soapRequest->getLocation(),
                $soapRequest->getAction(),
                $soapRequest->getVersion(),
                $curlResponse->getHttpResponseContentType(),
                $soapResponseTracingData,
                $soapAttachments
            );

        } else {

            return SoapResponseFactory::create(
                $curlResponse->getResponseBody(),
                $soapRequest->getLocation(),
                $soapRequest->getAction(),
                $soapRequest->getVersion(),
                $curlResponse->getHttpResponseContentType(),
                $soapAttachments
            );
        }
    }

    private function throwSoapFaultByTracing($isTracingEnabled, $soapFaultCode, $soapFaultMessage, SoapResponseTracingData $soapResponseTracingData)
    {
        if ($isTracingEnabled === true) {

            throw new SoapFaultWithTracingData(
                $soapFaultCode,
                $soapFaultMessage,
                $soapResponseTracingData
            );

        } else {

            throw new SoapFault(
                $soapFaultCode,
                $soapFaultMessage
            );
        }
    }

    private function checkTracing()
    {
        if ($this->soapClientOptions->getTrace() === false) {
            throw new Exception('SoapClientOptions tracing disabled, turn on trace attribute');
        }
    }
}
