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

use BeSimple\SoapCommon\SoapMessage;

class SoapResponseFactory
{
    /**
     * Factory function for SoapResponse.
     *
     * @param string $content  Content
     * @param string $location Location
     * @param string $action   SOAP action
     * @param string $version  SOAP version
     * @param array $attachments SOAP attachments
     *
     * @return SoapResponse
     */
    public static function create($content, $location, $action, $version, $attachments = [])
    {
        $response = new SoapResponse();
        $response->setContent($content);
        $response->setLocation($location);
        $response->setAction($action);
        $response->setVersion($version);
        $contentType = SoapMessage::getContentTypeForVersion($version);
        $response->setContentType($contentType);

        return $response;
    }
}
