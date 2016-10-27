<?php

namespace BeSimple\SoapServer;

use BeSimple\SoapServer\SoapOptions\SoapServerOptions;

class SoapServerOptionsBuilder
{
    static public function createWithDefaults($handlerClassOrObject)
    {
        return new SoapServerOptions(
            $handlerClassOrObject,
            SoapServerOptions::SOAP_SERVER_KEEP_ALIVE_OFF,
            SoapServerOptions::SOAP_SERVER_ERROR_REPORTING_OFF,
            SoapServerOptions::SOAP_SERVER_EXCEPTIONS_ON,
            SoapServerOptions::SOAP_SERVER_PERSISTENCE_NONE
        );
    }
}
