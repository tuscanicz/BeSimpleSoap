<?php

namespace BeSimple\SoapClient;

class SoapResponseTracingData
{
    private $lastRequestHeaders;
    private $lastRequest;
    private $lastResponseHeaders;
    private $lastResponse;

    public function __construct($lastRequestHeaders, $lastRequest, $lastResponseHeaders, $lastResponse)
    {
        $this->lastRequestHeaders = $lastRequestHeaders;
        $this->lastRequest = $lastRequest;
        $this->lastResponseHeaders = $lastResponseHeaders;
        $this->lastResponse = $lastResponse;
    }

    public function getLastRequestHeaders()
    {
        return $this->lastRequestHeaders;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
