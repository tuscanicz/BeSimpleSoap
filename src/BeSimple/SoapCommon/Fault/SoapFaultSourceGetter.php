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
        $nativeSoapFaultPrefix = SoapFaultPrefixEnum::PREFIX_DEFAULT.'-';

        if (strpos($soapFault->faultcode, $nativeSoapFaultPrefix) === 0) {

            return true;
        }

        return false;
    }
}
