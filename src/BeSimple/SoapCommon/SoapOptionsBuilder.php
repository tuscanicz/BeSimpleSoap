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
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapOptionsBuilder
{
    public static function createWithDefaults(
        $wsdlFile,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null
    ) {
        return self::createWithClassMap($wsdlFile, new ClassMap(), $wsdlCacheType, $wsdlCacheDir);
    }

    public static function createWithDefaultsKeepAlive(
        $wsdlFile,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null
    ) {
        return self::createWithClassMapKeepAlive($wsdlFile, new ClassMap(), $wsdlCacheType, $wsdlCacheDir);
    }

    public static function createSwaWithClassMap(
        $wsdlFile,
        ClassMap $classMap,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null
    ) {
        return self::createWithClassMap(
            $wsdlFile,
            $classMap,
            $wsdlCacheType,
            $wsdlCacheDir,
            SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA
        );
    }

    public static function createSwaWithClassMapV11(
        $wsdlFile,
        ClassMap $classMap,
        $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE,
        $wsdlCacheDir = null
    ) {
        return self::createWithClassMapV11(
            $wsdlFile,
            $classMap,
            $wsdlCacheType,
            $wsdlCacheDir,
            SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA
        );
    }

    public static function createWithClassMap(
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

        return new SoapOptions(
            SoapOptions::SOAP_VERSION_1_2,
            SoapOptions::SOAP_ENCODING_UTF8,
            SoapOptions::SOAP_CONNECTION_KEEP_ALIVE_OFF,
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
    }

    public static function createWithClassMapKeepAlive(
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

        return new SoapOptions(
            SoapOptions::SOAP_VERSION_1_2,
            SoapOptions::SOAP_ENCODING_UTF8,
            SoapOptions::SOAP_CONNECTION_KEEP_ALIVE_ON,
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
    }

    public static function createWithClassMapV11(
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

        return new SoapOptions(
            SoapOptions::SOAP_VERSION_1_1,
            SoapOptions::SOAP_ENCODING_UTF8,
            SoapOptions::SOAP_CONNECTION_KEEP_ALIVE_OFF,
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
    }
}
