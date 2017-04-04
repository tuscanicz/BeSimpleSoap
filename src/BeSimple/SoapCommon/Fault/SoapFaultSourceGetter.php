<?php

namespace BeSimple\SoapCommon\Fault;

use SoapFault;

class SoapFaultSourceGetter
{
    public static function isNativeSoapFault(SoapFault $soapFault)
    {
        return self::isBeSimpleSoapFault($soapFault) === false;
    }

    public static function isBeSimpleSoapFault(SoapFault $soapFault)
    {
        $defaultPrefix = SoapFaultPrefixEnum::PREFIX_DEFAULT;

        if (strpos($soapFault->getCode(), $defaultPrefix) === 0) {

            return false;
        }

        return true;
    }
}
