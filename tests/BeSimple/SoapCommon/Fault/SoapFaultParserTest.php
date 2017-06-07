<?php

namespace BeSimple\SoapCommon\Fault;

use PHPUnit_Framework_TestCase;
use SoapFault;

class SoapFaultParserTest extends PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $soapFaultXml = '<?xml version="1.0" encoding="UTF-8"?>'.
            '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'.
                '<SOAP-ENV:Body>'.
                    '<SOAP-ENV:Fault>'.
                        '<faultcode>911</faultcode>'.
                        '<faultstring>This is a dummy SoapFault.</faultstring>'.
                    '</SOAP-ENV:Fault>'.
                '</SOAP-ENV:Body>'.
            '</SOAP-ENV:Envelope>';
        $soapFault = SoapFaultParser::parseSoapFault($soapFaultXml);

        self::assertInstanceOf(SoapFault::class, $soapFault);
        self::assertEquals(
            '911',
            $soapFault->faultcode
        );
        self::assertEquals(
            'This is a dummy SoapFault.',
            $soapFault->getMessage()
        );
    }
}
