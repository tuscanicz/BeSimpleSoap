<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use Exception;

trait SoapClientNativeMethodsTrait
{
    protected $soapClientOptions;
    /** @var SoapAttachment[] */
    private $soapAttachmentsOnRequestStorage;
    /** @var SoapResponse */
    private $soapResponseStorage;

    /**
     * @param string $functionName
     * @param array $arguments
     * @param array|null $options
     * @param SoapAttachment[] $soapAttachments
     * @param null $inputHeaders
     * @param array|null $outputHeaders
     * @return SoapResponse
     */
    abstract public function soapCall($functionName, array $arguments, array $soapAttachments = [], array $options = null, $inputHeaders = null, array &$outputHeaders = null);

    /**
     * @param mixed $request Request object
     * @param string $location Location
     * @param string $action SOAP action
     * @param int $version SOAP version
     * @param SoapAttachment[] $soapAttachments SOAP attachments array
     * @return SoapResponse
     */
    abstract protected function performSoapRequest($request, $location, $action, $version, array $soapAttachments = []);

    /**
     * @return SoapClientOptions
     */
    abstract protected function getSoapClientOptions();

    /**
     * @return SoapOptions
     */
    abstract protected function getSoapOptions();

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
    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null)
    {
        return $this->soapCall($function_name, $arguments, $options, $input_headers, $output_headers)->getResponseContent();
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
        $soapResponse = $this->performSoapRequest(
            $request,
            $location,
            $action,
            $version,
            $this->getSoapAttachmentsOnRequestFromStorage()
        );
        $this->setSoapResponseToStorage($soapResponse);

        return $soapResponse->getResponseContent();
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

    private function checkTracing()
    {
        if ($this->getSoapClientOptions()->getTrace() === false) {
            throw new Exception('SoapClientOptions tracing disabled, turn on trace attribute');
        }
    }

    private function setSoapResponseToStorage(SoapResponse $soapResponseStorage)
    {
        $this->soapResponseStorage = $soapResponseStorage;
    }

    /**
     * @param SoapAttachment[] $soapAttachments
     */
    private function setSoapAttachmentsOnRequestToStorage(array $soapAttachments)
    {
        $this->soapAttachmentsOnRequestStorage = $soapAttachments;
    }

    private function getSoapAttachmentsOnRequestFromStorage()
    {
        $soapAttachmentsOnRequest = $this->soapAttachmentsOnRequestStorage;
        $this->soapAttachmentsOnRequestStorage = null;

        return $soapAttachmentsOnRequest;
    }

    private function getSoapResponseFromStorage()
    {
        $soapResponse = $this->soapResponseStorage;
        $this->soapResponseStorage = null;

        return $soapResponse;
    }
}
