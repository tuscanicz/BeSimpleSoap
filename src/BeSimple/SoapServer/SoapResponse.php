<?php

namespace BeSimple\SoapServer;

use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;

class SoapResponse extends CommonSoapResponse
{
    public function getResponseContent()
    {
        return $this->getContent();
    }
}
