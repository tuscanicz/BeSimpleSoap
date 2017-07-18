<?php

namespace BeSimple\SoapClient\Xml;

use Exception;

class RemoteFileResolver
{
    public static function instantiateResolver()
    {
        return new self();
    }

    /**
     * @param string $wsdlPath File URL/path
     * @return boolean
     */
    public function isRemoteFile($wsdlPath)
    {
        $parsedUrlOrFalse = @parse_url($wsdlPath);
        if ($parsedUrlOrFalse !== false) {
            if (isset($parsedUrlOrFalse['scheme']) && strpos($parsedUrlOrFalse['scheme'], 'http') === 0) {

                return true;
            }

            return false;
        }

        throw new Exception('Could not determine wsdlPath is remote: '.$wsdlPath);
    }
}
