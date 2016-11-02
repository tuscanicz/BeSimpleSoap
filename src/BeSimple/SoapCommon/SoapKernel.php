<?php

namespace BeSimple\SoapCommon;

use BeSimple\SoapCommon\Mime\Part as MimePart;

/**
 * SoapKernel provides methods to pre- and post-process SoapRequests and SoapResponses using
 * chains of SoapRequestFilter and SoapResponseFilter objects (roughly following
 * the chain-of-responsibility pattern).
 *
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyně <petr.bechyne@vodafone.com>
 */
class SoapKernel
{
    /**
    * Add attachment.
    *
    * @param \BeSimple\SoapCommon\Mime\Part $attachment New attachment
    *
    * @return void
    */
    public function addAttachment(MimePart $attachment)
    {
        $contentId = trim($attachment->getHeader('Content-ID'), '<>');

        $this->attachments[$contentId] = $attachment;
    }

    /**
     * Get attachment and remove from array.
     *
     * @param string $contentId Content ID of attachment
     *
     * @return \BeSimple\SoapCommon\Mime\Part|null
     */
    public function getAttachment($contentId)
    {
        if (isset($this->attachments[$contentId])) {
            $part = $this->attachments[$contentId];
            unset($this->attachments[$contentId]);

            return $part;
        }

        return null;
    }


    /**
     * Applies all registered SoapRequestFilter to the given SoapRequest.
     *
     * @param SoapRequest $request Soap request
     * @param SoapRequestFilter[]|SoapResponseFilter[] $filters
     * @param int $attachmentType = SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA|SoapOptions::ATTACHMENTS_TYPE_MTOM|SoapOptions::ATTACHMENTS_TYPE_BASE64
     * @return SoapRequest
     */
    public function filterRequest(SoapRequest $request, array $filters, $attachmentType)
    {
        foreach ($filters as $filter) {
            if ($filter instanceof SoapRequestFilter) {
                $request = $filter->filterRequest($request, $attachmentType);
            }
        }

        return $request;
    }

    /**
     * Applies all registered SoapResponseFilter to the given SoapResponse.
     *
     * @param SoapResponse $response SOAP response
     * @param SoapRequestFilter[]|SoapResponseFilter[] $filters
     * @param int $attachmentType = SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA|SoapOptions::ATTACHMENTS_TYPE_MTOM|SoapOptions::ATTACHMENTS_TYPE_BASE64
     * @return SoapResponse
     */
    public function filterResponse(SoapResponse $response, array $filters, $attachmentType)
    {
        foreach ($filters as $filter) {
            if ($filter instanceof SoapResponseFilter) {
                $response = $filter->filterResponse($response, $attachmentType);
            }
        }

        return $response;
    }
}
