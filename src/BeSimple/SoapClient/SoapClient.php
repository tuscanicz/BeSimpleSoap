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

use BeSimple\SoapCommon\SoapKernel;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapRequestFactory;

/**
 * Extended SoapClient that uses a a cURL wrapper for all underlying HTTP
 * requests in order to use proper authentication for all requests. This also
 * adds NTLM support. A custom WSDL downloader resolves remote xsd:includes and
 * allows caching of all remote referenced items.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class SoapClient extends \SoapClient
{
    protected $soapVersion;
    protected $tracingEnabled;
    protected $soapClientOptions;
    protected $soapOptions;
    protected $curl;

    /**
     * Last request headers.
     *
     * @var string
     */
    private $lastRequestHeaders = '';

    /**
     * Last request.
     *
     * @var string
     */
    private $lastRequest = '';

    /**
     * Last response headers.
     *
     * @var string
     */
    private $lastResponseHeaders = '';

    /**
     * Last response.
     *
     * @var string
     */
    private $lastResponse = '';

    /**
     * Constructor.
     *
     * @param SoapClientOptions $soapClientOptions
     * @param  SoapOptions $soapOptions
     */
    public function __construct(SoapClientOptions $soapClientOptions, SoapOptions $soapOptions)
    {
        $this->soapVersion = $soapOptions->getSoapVersion();
        $this->tracingEnabled = $soapClientOptions->getTrace();
        $this->soapClientOptions = $soapClientOptions;
        $this->soapOptions = $soapOptions;

        // @todo: refactor SoapClient: do not use $options as array: refactor Curl
        $this->curl = new Curl($soapClientOptions->toArray());

        $wsdlFile = $this->loadWsdl(
            $soapOptions->getWsdlFile(),
            $soapOptions->getWsdlCacheType()
        );

        parent::__construct(
            $wsdlFile,
            $soapClientOptions->toArray() + $soapOptions->toArray()
        );
    }

    /**
     * Custom request method to be able to modify the SOAP messages.
     * $oneWay parameter is not used at the moment.
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
        $soapRequest = $this->createSoapRequest($location, $action, $version, $request);
        $soapResponse = $this->getSoapResponseFromRequest($soapRequest);

        return $soapResponse->getContent();
    }

    private function createSoapRequest($location, $action, $version, $request)
    {
        $soapRequest = SoapRequestFactory::create($location, $action, $version, $request);
        if ($this->soapOptions->hasAttachments()) {
            $soapKernel = new SoapKernel();
            $soapRequest = $soapKernel->filterRequest(
                $soapRequest,
                $this->getAttachmentFilters(),
                $this->soapOptions->getAttachmentType()
            );
        }

        return $soapRequest;
    }

    /**
     * Runs the currently registered request filters on the request, performs
     * the HTTP request and runs the response filters.
     *
     * @param SoapRequest $soapRequest SOAP request object
     *
     * @return SoapResponse
     */
    private function getSoapResponseFromRequest(SoapRequest $soapRequest)
    {
        $soapResponse = $this->performHttpSoapRequest($soapRequest);
        if ($this->soapOptions->hasAttachments()) {
            $soapKernel = new SoapKernel();
            $soapKernel->filterResponse($soapResponse, $this->getAttachmentFilters(), $this->soapOptions->getAttachmentType());
        }

        return $soapResponse;
    }

    /**
     * Perform HTTP request with cURL.
     *
     * @param SoapRequest $soapRequest SoapRequest object
     * @return SoapResponse
     */
    private function performHttpSoapRequest(SoapRequest $soapRequest)
    {
        // HTTP headers
        $soapVersion = $soapRequest->getVersion();
        $soapAction = $soapRequest->getAction();
        if (SOAP_1_1 === $soapVersion) {
            $headers = [
                'Content-Type:' . $soapRequest->getContentType(),
                'SOAPAction: "' . $soapAction . '"',
            ];
        } else {
            $headers = [
               'Content-Type:' . $soapRequest->getContentType() . '; action="' . $soapAction . '"',
            ];
        }

        $location = $soapRequest->getLocation();
        $content = $soapRequest->getContent();

        $headers = $this->filterRequestHeaders($soapRequest, $headers);

        $options = $this->filterRequestOptions($soapRequest);

        $responseSuccessful = $this->curl->exec(
            $location,
            $content,
            $headers,
            $options
        );

        // tracing enabled: store last request header and body
        if ($this->tracingEnabled === true) {
            $this->lastRequestHeaders = $this->curl->getRequestHeaders();
            $this->lastRequest = $soapRequest->getContent();
        }
        // in case of an error while making the http request throw a soapFault
        if ($responseSuccessful === false) {
            // get error message from curl
            $faultstring = $this->curl->getErrorMessage();
            throw new \SoapFault('HTTP', $faultstring);
        }
        // tracing enabled: store last response header and body
        if ($this->tracingEnabled === true) {
            $this->lastResponseHeaders = $this->curl->getResponseHeaders();
            $this->lastResponse = $this->curl->getResponseBody();
        }
        // wrap response data in SoapResponse object
        $soapResponse = SoapResponse::create(
            $this->curl->getResponseBody(),
            $soapRequest->getLocation(),
            $soapRequest->getAction(),
            $soapRequest->getVersion(),
            $this->curl->getResponseContentType()
        );

        return $soapResponse;
    }

    /**
     * Filters HTTP headers which will be sent
     *
     * @param SoapRequest $soapRequest SOAP request object
     * @param array       $headers     An array of HTTP headers
     *
     * @return array
     */
    protected function filterRequestHeaders(SoapRequest $soapRequest, array $headers)
    {
        return $headers;
    }

    /**
     * Adds additional cURL options for the request
     *
     * @param SoapRequest $soapRequest SOAP request object
     *
     * @return array
     */
    protected function filterRequestOptions(SoapRequest $soapRequest)
    {
        return [];
    }

    /**
     * Get last request HTTP headers.
     *
     * @return string
     */
    public function __getLastRequestHeaders()
    {
        return $this->lastRequestHeaders;
    }

    /**
     * Get last request HTTP body.
     *
     * @return string
     */
    public function __getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Get last response HTTP headers.
     *
     * @return string
     */
    public function __getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * Get last response HTTP body.
     *
     * @return string
     */
    public function __getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param string $wsdl
     * @param int $wsdlCache
     * @param bool $resolveRemoteIncludes
     *
     * @return string
     */
    private function loadWsdl($wsdl, $wsdlCache, $resolveRemoteIncludes = true)
    {
        $wsdlDownloader = new WsdlDownloader($this->curl, $resolveRemoteIncludes, $wsdlCache);
        try {
            $cacheFileName = $wsdlDownloader->download($wsdl);
        } catch (\RuntimeException $e) {
            throw new \SoapFault('WSDL', "SOAP-ERROR: Parsing WSDL: Couldn't load from '" . $wsdl . "'");
        }

        return $cacheFileName;
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
}
