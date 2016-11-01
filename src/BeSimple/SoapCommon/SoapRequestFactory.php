<?php

namespace BeSimple\SoapCommon;

class SoapRequestFactory
{
    /**
     * Factory function for SoapRequest.
     *
     * @param string $location Location
     * @param string $action   SOAP action
     * @param string $version  SOAP version
     * @param string $content  Content
     *
     * @return SoapRequest
     */
    public static function create($location, $action, $version, $content = null)
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
