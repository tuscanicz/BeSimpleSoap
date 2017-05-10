<?php

namespace BeSimple\SoapCommon\Fault;

class SoapFaultEnum
{
    const SOAP_FAULT_WSDL = SoapFaultPrefixEnum::PREFIX_DEFAULT.'-'.'wsdl';
    const SOAP_FAULT_HTTP = SoapFaultPrefixEnum::PREFIX_DEFAULT.'-'.'http';
    const SOAP_FAULT_SOAP_CLIENT_ERROR = SoapFaultPrefixEnum::PREFIX_DEFAULT.'-'.'soap-client-error';
}
