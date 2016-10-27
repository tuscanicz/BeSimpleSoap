<?php

/*
 * This file is part of the BeSimpleSoapBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapCommon;

use BeSimple\SoapCommon\Converter\TypeConverterCollection;
use BeSimple\SoapCommon\SoapOptions\SoapFeatures\SoapFeatures;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use InvalidArgumentException;

/**
 * @author Petr Bechyně <petr.bechyne@vodafone.com>
 */
class SoapOptionsBuilder
{
    static public function createWithDefaults($wsdlFile, $wsdlCacheType = Cache::TYPE_NONE)
    {
        if (!Cache::hasType($wsdlCacheType)) {
            throw new InvalidArgumentException;
        }
        $soapOptions = new SoapOptions(
            SoapOptions::SOAP_VERSION_1_2,
            SoapOptions::SOAP_ENCODING_UTF8,
            new SoapFeatures([
                SoapFeatures::SINGLE_ELEMENT_ARRAYS
            ]),
            $wsdlFile,
            $wsdlCacheType,
            new ClassMap(),
            new TypeConverterCollection()
        );

        return $soapOptions;
    }
}
