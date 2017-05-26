<?php

namespace BeSimple\SoapCommon\Fault;

use SimpleXMLElement;
use SoapFault;

class SoapFaultParser
{
    /**
     * @param string $soapFaultXmlSource
     * @return SoapFault
     */
    public static function parseSoapFault($soapFaultXmlSource)
    {
        $simpleXMLElement = new SimpleXMLElement($soapFaultXmlSource);
        $faultCode = $simpleXMLElement->xpath('//faultcode');
        if ($faultCode === false || count($faultCode) === 0) {
            $faultCode = 'Unable to parse faultCode';
        }
        $faultString = $simpleXMLElement->xpath('//faultstring');
        if ($faultString === false || count($faultString) === 0) {
            $faultString = 'Unable to parse faultString';
        }

        return new SoapFault(
            (string)$faultCode[0],
            (string)$faultString[0]
        );
    }
}
