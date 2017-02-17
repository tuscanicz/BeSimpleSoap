<?php

namespace BeSimple\SoapClient\Curl;

class CurlResponse
{
    private $httpRequestHeaders;
    private $httpResponseStatusCode;
    private $httpResponseStatusMessage;
    private $httpResponseContentType;
    private $curlStatus;
    private $curlErrorMessage;
    private $responseHeader;
    private $responseBody;

    public function __construct(
        $httpRequestHeaders,
        $httpResponseStatusCode,
        $httpResponseStatusMessage,
        $httpResponseContentType,
        $curlStatus,
        $responseHeader,
        $responseBody,
        $curlErrorMessage = null
    ) {
        $this->httpRequestHeaders = $httpRequestHeaders;
        $this->httpResponseStatusCode = $httpResponseStatusCode;
        $this->httpResponseStatusMessage = $httpResponseStatusMessage;
        $this->httpResponseContentType = $httpResponseContentType;
        $this->curlStatus = $curlStatus;
        $this->curlErrorMessage = $curlErrorMessage;
        $this->responseHeader = $responseHeader;
        $this->responseBody = $responseBody;
    }

    public function getHttpRequestHeaders()
    {
        return $this->httpRequestHeaders;
    }

    public function getHttpResponseStatusCode()
    {
        return $this->httpResponseStatusCode;
    }

    public function getHttpResponseStatusMessage()
    {
        return $this->httpResponseStatusMessage;
    }

    public function getHttpResponseContentType()
    {
        return $this->httpResponseContentType;
    }

    public function getCurlStatus()
    {
        return $this->curlStatus;
    }

    public function curlStatusSuccess()
    {
        return $this->curlStatus === Curl::CURL_SUCCESS;
    }

    public function curlStatusFailed()
    {
        return $this->curlStatus === Curl::CURL_FAILED;
    }

    public function hasCurlErrorMessage()
    {
        return $this->curlErrorMessage !== null;
    }

    public function getCurlErrorMessage()
    {
        return $this->curlErrorMessage;
    }

    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
