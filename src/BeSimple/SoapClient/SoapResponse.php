<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;

class SoapResponse extends CommonSoapResponse
{
    public function getResponseContent()
    {
        return $this->getContent();
    }
}
