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

namespace BeSimple\SoapClient;

use BeSimple\SoapBundle\Cache;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use Exception;
use SoapHeader;

/**
 * Provides a SoapClient instance.
 *
 * @author Francis Besset <francis.besset@gmail.com>
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapClientBuilder
{
    public function build(SoapClientOptions $soapClientOptions, SoapOptions $soapOptions)
    {
        $cache = new Cache($soapOptions);
        $cache->validateSettings($soapOptions);

        return new SoapClient(
            $soapClientOptions,
            $soapOptions
        );
    }

    public function buildWithSoapHeader(
        SoapClientOptions $soapClientOptions,
        SoapOptions $soapOptions,
        SoapHeader $soapHeader
    ) {
        $soapClient = $this->build($soapClientOptions, $soapOptions);
        if ($soapClient->__setSoapHeaders($soapHeader) === false) {
            throw new Exception(
                'Could not set SoapHeader: '.var_export($soapHeader, true)
            );
        }

        return $soapClient;
    }
}
