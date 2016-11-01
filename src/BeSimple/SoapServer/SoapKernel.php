<?php

namespace BeSimple\SoapServer;

use BeSimple\SoapCommon\SoapKernel;
use BeSimple\SoapCommon\SoapRequest;

/**
 * SoapKernel for Server.
 *
 * @todo-critical: kill this shit
 * @author Andreas Schamberger <mail@andreass.net>
 */
class SoapServerKernel extends SoapKernel
{
    /**
     * {@inheritDoc}
     */
    public function filterRequest(SoapRequest $request, array $filters)
    {
        parent::filterRequest($request, $filters);

        // attachments are now gone from here
    }

    /**
     * {@inheritDoc}
     */
    public function filterResponse(SoapResponse $response)
    {
        $response->setAttachments($this->attachments);
        $this->attachments = array();

        parent::filterResponse($response);
    }
}
