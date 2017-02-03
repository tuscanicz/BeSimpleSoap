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

namespace BeSimple\SoapServer;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapClient\SoapResponseTracingData;
use BeSimple\SoapCommon\Mime\PartFactory;
use BeSimple\SoapCommon\SoapMessage;

class SoapResponseFactory
{
    /**
     * Factory function for SoapServer\SoapResponse.
     *
     * @param string                    $content        Content
     * @param string                    $location       Location
     * @param string                    $action         SOAP action
     * @param string                    $version        SOAP version
     * @param SoapAttachment[]          $attachments    SOAP attachments
     *
     * @return SoapResponse
     */
    public static function create(
        $content,
        $location,
        $action,
        $version,
        array $attachments = []
    ) {
        $response = new SoapResponse();
        $response->setContent($content);
        $response->setLocation($location);
        $response->setAction($action);
        $response->setVersion($version);
        $contentType = SoapMessage::getContentTypeForVersion($version);
        $response->setContentType($contentType);
        if (count($attachments) > 0) {
            $response->setAttachments(
                PartFactory::createAttachmentParts($attachments)
            );
        }

        return $response;
    }

    /**
     * Factory function for SoapServer\SoapResponse.
     *
     * @param string                    $content        Content
     * @param string                    $location       Location
     * @param string                    $action         SOAP action
     * @param string                    $version        SOAP version
     * @param SoapResponseTracingData   $tracingData    Data value object suitable for tracing SOAP traffic
     * @param SoapAttachment[]          $attachments    SOAP attachments
     *
     * @return SoapResponse
     */
    public static function createWithTracingData(
        $content,
        $location,
        $action,
        $version,
        SoapResponseTracingData $tracingData,
        array $attachments = []
    ) {
        $response = new SoapResponse();
        $response->setContent($content);
        $response->setLocation($location);
        $response->setAction($action);
        $response->setVersion($version);
        $response->setTracingData($tracingData);
        $contentType = SoapMessage::getContentTypeForVersion($version);
        $response->setContentType($contentType);
        if (count($attachments) > 0) {
            $response->setAttachments(
                PartFactory::createAttachmentParts($attachments)
            );
        }

        return $response;
    }
}
