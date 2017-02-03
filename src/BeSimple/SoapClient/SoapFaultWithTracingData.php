<?php

namespace BeSimple\SoapClient;

use SoapFault;

class SoapFaultWithTracingData extends SoapFault
{
    private $soapResponseTracingData;

    public function __construct($code = 0, $message = "", SoapResponseTracingData $soapResponseTracingData)
    {
        $this->soapResponseTracingData = $soapResponseTracingData;
        parent::__construct($code, $message);
    }

    public function getSoapResponseTracingData()
    {
        return $this->soapResponseTracingData;
    }
}
