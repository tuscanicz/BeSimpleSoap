<?php

namespace BeSimple\SoapCommon\Fault;

use PHPUnit_Framework_TestCase;
use SoapFault;

class SoapFaultSourceGetterTest extends PHPUnit_Framework_TestCase
{
    const FIXTURES_DIR = __DIR__ . '/../../Fixtures';

    /**
     * @param SoapFault $soapFault
     * @dataProvider provideNativeSoapFaults
     */
    public function testWithNativeSoapFault(SoapFault $soapFault)
    {
        self::assertTrue(SoapFaultSourceGetter::isNativeSoapFault($soapFault));
        self::assertFalse(SoapFaultSourceGetter::isBeSimpleSoapFault($soapFault));
    }

    /**
     * @param SoapFault $soapFault
     * @dataProvider provideBeSimpleSoapFaults
     */
    public function testWithBeSimpleSoapFault(SoapFault $soapFault)
    {
        self::assertFalse(SoapFaultSourceGetter::isNativeSoapFault($soapFault));
        self::assertTrue(SoapFaultSourceGetter::isBeSimpleSoapFault($soapFault));
    }

    public function provideNativeSoapFaults()
    {
        return [
            [$this->getNativeSoapFaultFromClient()],
            // @todo: add more test cases for Soap Server \SoapFault
        ];
    }

    public function provideBeSimpleSoapFaults()
    {
        return [
            [new SoapFault(SoapFaultEnum::SOAP_FAULT_HTTP, 'HTTP Connection error')],
            [new SoapFault(SoapFaultEnum::SOAP_FAULT_SOAP_CLIENT_ERROR, 'SOAP Client error')],
            [new SoapFault(SoapFaultEnum::SOAP_FAULT_WSDL, 'WSDL error')],
        ];
    }

    /**
     * @return SoapFault
     */
    private function getNativeSoapFaultFromClient()
    {
        try {
            $soapClient = @new \SoapClient('non-existing-wsdl-throwing-soapfault');
            $soapClient->__call('no-function', []);
        } catch (SoapFault $e) {

            return $e;
        }

        self::fail('Cannot generate native PHP SoapFault from Client, please review the test');
    }
}
