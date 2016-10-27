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


/**
 * SOAP request message.
 *
 * @author Christian Kerl <christian-kerl@web.de>
 */
class SoapRequest extends SoapMessage
{
    /**
     * Factory function for SoapRequest.
     *
     * @param string $content  Content
     * @param string $location Location
     * @param string $action   SOAP action
     * @param string $version  SOAP version
     *
     * @return SoapRequest
     */
    public static function create($content, $location, $action, $version)
    {
        $request = new SoapRequest();
        // $content is if unmodified from SoapClient not a php string type!
        $request->setContent((string) $content);
        $request->setLocation($location);
        $request->setAction($action);
        $request->setVersion($version);
        $contentType = SoapMessage::getContentTypeForVersion($version);
        $request->setContentType($contentType);

        return $request;
    }
}
