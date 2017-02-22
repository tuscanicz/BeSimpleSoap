<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;

class SoapResponse extends CommonSoapResponse
{
    /** @var mixed */
    protected $responseObject;
    /** @var SoapResponseTracingData */
    protected $tracingData;

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
}
