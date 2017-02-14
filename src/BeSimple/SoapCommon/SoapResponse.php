<?php

/*
 * This file is part of the BeSimpleSoapCommon.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 * (c) Andreas Schamberger <mail@andreass.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapCommon;

use BeSimple\SoapClient\SoapResponseTracingData;

/**
 * SOAP response message.
 *
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapResponse extends SoapMessage
{
    /** @var SoapResponseTracingData */
    protected $tracingData;

    public function hasTracingData()
    {
        return $this->tracingData !== null;
    }

    public function getTracingData()
    {
        return $this->tracingData;
    }

    public function setTracingData(SoapResponseTracingData $tracingData)
    {
        $this->tracingData = $tracingData;
    }
}
