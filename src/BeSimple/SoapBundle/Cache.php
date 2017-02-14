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

namespace BeSimple\SoapBundle;

use BeSimple\SoapCommon\Cache as BaseCache;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use Exception;

/**
 * @author Francis Besset <francis.besset@gmail.com>
 */
class Cache
{
    public function __construct(SoapOptions $soapOptions)
    {
        if ($soapOptions->isWsdlCached()) {
            $isEnabled = (bool)$soapOptions->isWsdlCached() ? BaseCache::ENABLED : BaseCache::DISABLED;

            BaseCache::setEnabled($isEnabled);
            BaseCache::setType($soapOptions->getWsdlCacheType());
            BaseCache::setDirectory($soapOptions->getWsdlCacheDir());
        } else {
            BaseCache::setEnabled(BaseCache::DISABLED);
            BaseCache::setType(SoapOptions::SOAP_CACHE_TYPE_NONE);
            BaseCache::setDirectory(null);
        }
    }

    public function validateSettings(SoapOptions $soapOptions)
    {
        if ($soapOptions->isWsdlCached()) {
            if (BaseCache::isEnabled() !== true) {
                throw new Exception('WSDL cache could not be set');
            }
            if ($soapOptions->getWsdlCacheType() !== (int)BaseCache::getType()) {
                throw new Exception('WSDL cache type could not be set, ini settings is: '.BaseCache::getType());
            }
            if ($soapOptions->getWsdlCacheDir() !== BaseCache::getDirectory()) {
                throw new Exception('WSDL cache dir could not be set, real dir is: '.BaseCache::getDirectory());
            }
        } else {
            if (BaseCache::isEnabled() !== false) {
                throw new Exception('WSDL cache could not be turned off');
            }
        }
    }
}
