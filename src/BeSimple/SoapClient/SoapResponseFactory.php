<?php

/*
 * This file is part of the BeSimpleSoapClient.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapClient;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapCommon\Mime\PartFactory;
use BeSimple\SoapCommon\SoapRequest;

/**
 * SoapResponseFactory for SoapClient. Provides factory function for SoapResponse object.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapResponseFactory
{
    /**
     * Factory method for SoapClient\SoapResponse.
     *
     * @param SoapRequest               $soapRequest    related request object
     * @param string                    $content        Content
     * @param string                    $contentType    Content type header
     * @param SoapAttachment[]          $attachments    SOAP attachments
     * @return SoapResponse
     */
    public static function create(
        SoapRequest $soapRequest,
        $content,
        $contentType,
        array $attachments = []
    ) {
        $response = new SoapResponse();
        $response->setRequest($soapRequest);
        $response->setContent($content);
        $response->setLocation($soapRequest->getLocation());
        $response->setAction($soapRequest->getAction());
        $response->setVersion($soapRequest->getVersion());
        $response->setContentType($contentType);
        if (count($attachments) > 0) {
            $response->setAttachments(
                PartFactory::createAttachmentParts($attachments)
            );
        }

        return $response;
    }

    /**
     * Factory method for SoapClient\SoapResponse with SoapResponseTracingData.
     *
     * @param SoapRequest               $soapRequest    related request object
     * @param string                    $content        Content
     * @param string                    $contentType    Content type header
     * @param SoapResponseTracingData   $tracingData    Data value object suitable for tracing SOAP traffic
     * @param SoapAttachment[]          $attachments    SOAP attachments
     * @return SoapResponse
     */
    public static function createWithTracingData(
        SoapRequest $soapRequest,
        $content,
        $contentType,
        SoapResponseTracingData $tracingData,
        array $attachments = []
    ) {
        $response = new SoapResponse();
        $response->setRequest($soapRequest);
        $response->setContent($content);
        $response->setLocation($soapRequest->getLocation());
        $response->setAction($soapRequest->getAction());
        $response->setVersion($soapRequest->getVersion());
        $response->setContentType($contentType);
        $response->setTracingData($tracingData);
        if (count($attachments) > 0) {
            $response->setAttachments(
                PartFactory::createAttachmentParts($attachments)
            );
        }

        return $response;
    }
}
