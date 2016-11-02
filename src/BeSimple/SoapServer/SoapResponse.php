<?php

namespace BeSimple\SoapServer;

use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;

class SoapResponse extends CommonSoapResponse
{
    public function getResponseContent()
    {
        // set Content-Type header
        header('Content-Type: ' . $this->getContentType());

        return $this->getContent();
    }
}
