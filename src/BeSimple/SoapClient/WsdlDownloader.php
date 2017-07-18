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

use BeSimple\SoapClient\Curl\Curl;
use BeSimple\SoapClient\Xml\RemoteFileResolver;
use BeSimple\SoapClient\Xml\XmlFileDomDocumentProcessor;
use BeSimple\SoapCommon\Cache;
use BeSimple\SoapCommon\Helper;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

/**
 * Downloads WSDL files with cURL. Uses the WSDL_CACHE_* constants and the
 * 'soap.wsdl_*' ini settings. Does only file caching as SoapClient only
 * supports a file name parameter. The class also resolves remote XML schema
 * includes.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class WsdlDownloader
{
    public static function instantiateDownloader()
    {
        return new self();
    }

    /**
     * @param Curl $curl
     * @param string $wsdlPath WSDL file URL/path
     * @param int $wsdCacheType = Cache::TYPE_NONE|Cache::WSDL_CACHE_DISK|Cache::WSDL_CACHE_BOTH|Cache::WSDL_CACHE_MEMORY
     * @param boolean $resolveRemoteIncludes
     * @return string
     */
    public function getWsdlPath(Curl $curl, $wsdlPath, $wsdCacheType, $resolveRemoteIncludes = true)
    {
        $isRemoteFile = RemoteFileResolver::instantiateResolver()->isRemoteFile($wsdlPath);
        $isCacheEnabled = $wsdCacheType === Cache::TYPE_NONE ? false : Cache::isEnabled();
        if ($isCacheEnabled === true) {
            $cacheFilePath = Cache::getDirectory() . DIRECTORY_SEPARATOR . 'wsdl_' . md5($wsdlPath) . '.cache';
            $isCacheExisting = file_exists($cacheFilePath);
            if ($isCacheExisting) {
                $fileModificationTime = filemtime($cacheFilePath);
                if ($fileModificationTime === false) {
                    throw new Exception('File modification time could not be get for wsdl path: ' . $cacheFilePath);
                }
                $isCacheValid = ($fileModificationTime + Cache::getLifetime()) >= time();
            } else {
                $isCacheExisting = $isCacheValid = false;
            }
            if ($isCacheExisting === false || $isCacheValid === false) {
                XmlFileDomDocumentProcessor::writeCacheFile($curl, $wsdCacheType, $wsdlPath, $cacheFilePath, $resolveRemoteIncludes, $isRemoteFile);
            }

            return $this->getLocalWsdlPath($cacheFilePath);

        }
        if ($isRemoteFile === true) {
            return $wsdlPath;
        }

        return $this->getLocalWsdlPath($wsdlPath);
    }

    private function getLocalWsdlPath($wsdlPath)
    {
        if (file_exists($wsdlPath)) {

            return realpath($wsdlPath);

        }

        throw new Exception('Could not download WSDL: local file does not exist: ' . $wsdlPath);
    }
}
