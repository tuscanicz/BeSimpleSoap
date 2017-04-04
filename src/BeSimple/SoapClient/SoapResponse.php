<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;

class SoapResponse extends CommonSoapResponse
{
    /** @var mixed */
    protected $responseObject;
    /** @var SoapResponseTracingData */
    protected $tracingData;
    /** @var SoapRequest */
    protected $request;

    public function getResponseContent()
    {
        return $this->getContent();
    }

    public function getResponseObject()
    {
        return $this->responseObject;
    }

    public function setResponseObject($responseObject)
    {
        $this->responseObject = $responseObject;
    }

    public function hasTracingData()
    {
        return $this->tracingData !== null;
    }

    public function getTracingData()
    {
        return $this->tracingData;
    }

    public function setTracingData(SoapResponseTracingData $tracingData)
    {
        $this->tracingData = $tracingData;
    }

    public function setRequest(SoapRequest $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
