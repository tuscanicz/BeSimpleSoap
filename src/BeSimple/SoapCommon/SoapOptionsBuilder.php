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
    static public function createWithDefaults(
        $wsdlFile,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null
    ) {
        return self::createWithClassMap($wsdlFile, new ClassMap(), $wsdlCacheType, $wsdlCacheDir);
    }

    static public function createSwaWithClassMap(
        $wsdlFile,
        ClassMap $classMap,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null
    ) {
        return self::createWithClassMap($wsdlFile, $classMap, $wsdlCacheType, $wsdlCacheDir, SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA);
    }

    static public function createWithClassMap(
        $wsdlFile,
        ClassMap $classMap,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null,
        $attachmentType = null
    ) {
        if (!Cache::hasType($wsdlCacheType)) {
            throw new InvalidArgumentException('Invalid cache type');
        }
        if ($wsdlCacheType !== SoapOptions::SOAP_CACHE_TYPE_NONE) {
            if ($wsdlCacheDir === null) {
                throw new InvalidArgumentException('Cache dir must be set for this wsdl cache type');
            }
        }
        $soapOptions = new SoapOptions(
            SoapOptions::SOAP_VERSION_1_2,
            SoapOptions::SOAP_ENCODING_UTF8,
            new SoapFeatures([
                SoapFeatures::SINGLE_ELEMENT_ARRAYS
            ]),
            $wsdlFile,
            $wsdlCacheType,
            $wsdlCacheDir,
            $classMap,
            new TypeConverterCollection(),
            $attachmentType
        );

        return $soapOptions;
    }
}
