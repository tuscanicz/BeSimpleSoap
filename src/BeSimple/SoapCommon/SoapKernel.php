<?php

namespace BeSimple\SoapCommon;

/**
 * SoapKernel provides methods to pre- and post-process SoapRequests and SoapResponses using
 * chains of SoapRequestFilter and SoapResponseFilter objects (roughly following
 * the chain-of-responsibility pattern).
 *
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapKernel
{
    /**
     * Applies all registered SoapRequestFilter to the given SoapRequest.
     *
     * @param SoapRequest $request Soap request
     * @param SoapRequestFilter[]|SoapResponseFilter[] $filters
     * @param int $attachmentType = SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA|SoapOptions::ATTACHMENTS_TYPE_MTOM|SoapOptions::ATTACHMENTS_TYPE_BASE64
     * @return SoapRequest
     */
    public static function filterRequest(SoapRequest $request, array $filters, $attachmentType)
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
     * @return \BeSimple\SoapClient\SoapResponse|\BeSimple\SoapServer\SoapResponse
     */
    public static function filterResponse(SoapResponse $response, array $filters, $attachmentType)
    {
        foreach ($filters as $filter) {
            if ($filter instanceof SoapResponseFilter) {
                $response = $filter->filterResponse($response, $attachmentType);
            }
        }

        return $response;
    }
}
