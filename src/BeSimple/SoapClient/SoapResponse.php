<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;

class SoapResponse extends CommonSoapResponse
{
    /**
     * @var mixed
     */
    protected $responseObject;

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
}
