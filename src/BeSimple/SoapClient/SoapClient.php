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

use BeSimple\SoapCommon\Helper;
use BeSimple\SoapCommon\Converter\MtomTypeConverter;
use BeSimple\SoapCommon\Converter\SwaTypeConverter;
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

    /**
     * Tracing enabled?
     *
     * @var boolean
     */
    protected $tracingEnabled = false;

    /**
     * cURL instance.
     *
     * @var \BeSimple\SoapClient\Curl
     */
    protected $curl = null;

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
     * Soap kernel.
     *
     * @var \BeSimple\SoapClient\SoapKernel
     */
    protected $soapKernel = null;

    /**
     * Constructor.
     *
     * @param SoapClientOptions $soapClientOptions
     * @param  SoapOptions $soapOptions
     */
    public function __construct(SoapClientOptions $soapClientOptions, SoapOptions $soapOptions)
    {
        $this->soapKernel = new SoapKernel();
        $this->soapVersion = $soapOptions->getSoapVersion();
        $this->tracingEnabled = $soapClientOptions->getTrace();

        // @todo: refactor SoapClient: do not use $options as array
        $options = $this->configureMime($soapOptions->toArray());

        // @todo: refactor SoapClient: do not use $options as array
        $this->curl = new Curl($soapClientOptions->toArray());

        // @todo: refactor SoapClient: do not use $options as array
        $wsdlFile = $this->loadWsdl($soapOptions->getWsdlFile(), $soapOptions->toArray());

        parent::__construct($wsdlFile, $options);
    }


    /**
     * Perform HTTP request with cURL.
     *
     * @param SoapRequest $soapRequest SoapRequest object
     *
     * @return SoapResponse
     */
    private function __doHttpRequest(SoapRequest $soapRequest)
    {
        // HTTP headers
        $soapVersion = $soapRequest->getVersion();
        $soapAction = $soapRequest->getAction();
        if (SOAP_1_1 == $soapVersion) {
            $headers = array(
                'Content-Type:' . $soapRequest->getContentType(),
                'SOAPAction: "' . $soapAction . '"',
            );
        } else {
            $headers = array(
               'Content-Type:' . $soapRequest->getContentType() . '; action="' . $soapAction . '"',
            );
        }

        $location = $soapRequest->getLocation();
        $content = $soapRequest->getContent();

        $headers = $this->filterRequestHeaders($soapRequest, $headers);

        $options = $this->filterRequestOptions($soapRequest);

        // execute HTTP request with cURL
        $responseSuccessfull = $this->curl->exec(
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
        if ($responseSuccessfull === false) {
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
     * Custom request method to be able to modify the SOAP messages.
     * $oneWay parameter is not used at the moment.
     *
     * @todo: refactor SoapClient: refactoring starts from here
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
        // wrap request data in SoapRequest object
        $soapRequest = SoapRequestFactory::create($location, $action, $version, $request);

        // do actual SOAP request
        $soapResponse = $this->__doRequest2($soapRequest);

        // return SOAP response to ext/soap
        return $soapResponse->getContent();
    }

    /**
     * Runs the currently registered request filters on the request, performs
     * the HTTP request and runs the response filters.
     *
     * @param SoapRequest $soapRequest SOAP request object
     *
     * @return SoapResponse
     */
    protected function __doRequest2(SoapRequest $soapRequest)
    {
        // run SoapKernel on SoapRequest
        $this->soapKernel->filterRequest($soapRequest);

        // perform HTTP request with cURL
        $soapResponse = $this->__doHttpRequest($soapRequest);

        // run SoapKernel on SoapResponse
        $this->soapKernel->filterResponse($soapResponse);

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
        return array();
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
     * Get SoapKernel instance.
     *
     * @return \BeSimple\SoapClient\SoapKernel
     */
    public function getSoapKernel()
    {
        return $this->soapKernel;
    }

    private function configureMime(array $options)
    {
        if (Helper::ATTACHMENTS_TYPE_BASE64 !== $options['attachment_type']) {
            // register mime filter in SoapKernel
            $mimeFilter = new MimeFilter($options['attachment_type']);
            $this->soapKernel->registerFilter($mimeFilter);
            // configure type converter
            if (Helper::ATTACHMENTS_TYPE_SWA === $options['attachment_type']) {
                $converter = new SwaTypeConverter();
                $converter->setKernel($this->soapKernel);
            } elseif (Helper::ATTACHMENTS_TYPE_MTOM === $options['attachment_type']) {
                $xmlMimeFilter = new XmlMimeFilter($options['attachment_type']);
                $this->soapKernel->registerFilter($xmlMimeFilter);
                $converter = new MtomTypeConverter();
                $converter->setKernel($this->soapKernel);
            }
            // configure typemap
            if (!isset($options['typemap'])) {
                $options['typemap'] = array();
            }
            $options['typemap'][] = array(
                'type_name' => $converter->getTypeName(),
                'type_ns'   => $converter->getTypeNamespace(),
                'from_xml'  => function($input) use ($converter) {
                    return $converter->convertXmlToPhp($input);
                },
                'to_xml'    => function($input) use ($converter) {
                    return $converter->convertPhpToXml($input);
                },
            );
        }

        return $options;
    }

    /**
     * Downloads WSDL files with cURL. Uses all SoapClient options for
     * authentication. Uses the WSDL_CACHE_* constants and the 'soap.wsdl_*'
     * ini settings. Does only file caching as SoapClient only supports a file
     * name parameter.
     *
     * @param string               $wsdl    WSDL file
     * @param array(string=>mixed) $options Options array
     *
     * @return string
     */
    protected function loadWsdl($wsdl, array $options)
    {
        // option to resolve wsdl/xsd includes
        $resolveRemoteIncludes = true;
        if (isset($options['resolve_wsdl_remote_includes'])) {
            $resolveRemoteIncludes = $options['resolve_wsdl_remote_includes'];
        }
        // option to enable cache
        $wsdlCache = WSDL_CACHE_DISK;
        if (isset($options['cache_wsdl'])) {
            $wsdlCache = $options['cache_wsdl'];
        }
        $wsdlDownloader = new WsdlDownloader($this->curl, $resolveRemoteIncludes, $wsdlCache);
        try {
            $cacheFileName = $wsdlDownloader->download($wsdl);
        } catch (\RuntimeException $e) {
            throw new \SoapFault('WSDL', "SOAP-ERROR: Parsing WSDL: Couldn't load from '" . $wsdl . "' : failed to load external entity \"" . $wsdl . "\"");
        }

        return $cacheFileName;
    }
}