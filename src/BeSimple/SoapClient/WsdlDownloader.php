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
    /**
     * @param Curl $curl
     * @param string $wsdlPath WSDL file URL/path
     * @param int $wsdCacheType = Cache::TYPE_NONE|Cache::WSDL_CACHE_DISK|Cache::WSDL_CACHE_BOTH|Cache::WSDL_CACHE_MEMORY
     * @param boolean $resolveRemoteIncludes
     * @return string
     */
    public function getWsdlPath(Curl $curl, $wsdlPath, $wsdCacheType, $resolveRemoteIncludes = true)
    {
        $isRemoteFile = $this->isRemoteFile($wsdlPath);
        $isCacheEnabled = $wsdCacheType === Cache::TYPE_NONE ? false : Cache::isEnabled();
        if ($isCacheEnabled === true) {
            $cacheFilePath = Cache::getDirectory().DIRECTORY_SEPARATOR.'wsdl_'.md5($wsdlPath).'.cache';
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
                $this->writeCacheFile($curl, $wsdCacheType, $wsdlPath, $cacheFilePath, $resolveRemoteIncludes, $isRemoteFile);
            }

            return $this->getLocalWsdlPath($cacheFilePath);

        } else {

            if ($isRemoteFile === true) {
                return $wsdlPath;
            }

            return $this->getLocalWsdlPath($wsdlPath);
        }
    }

    private function writeCacheFile(Curl $curl, $cacheType, $wsdlPath, $cacheFilePath, $resolveRemoteIncludes, $isRemoteFile)
    {
        if ($isRemoteFile === true) {
            $curlResponse = $curl->executeCurlWithCachedSession($wsdlPath);
            if ($curlResponse->curlStatusSuccess()) {
                if (mb_strlen($curlResponse->getResponseBody()) === 0) {
                    throw new Exception('Could not write WSDL cache file: curl response empty');
                }
                if ($resolveRemoteIncludes === true) {
                    $document = $this->getXmlFileDOMDocument($curl, $cacheType, $curlResponse->getResponseBody(), $wsdlPath);
                    $this->saveXmlDOMDocument($document, $cacheFilePath);
                } else {
                    file_put_contents($cacheFilePath, $curlResponse->getResponseBody());
                }
            } else {
                throw new Exception('Could not write WSDL cache file: Download failed with message: '.$curlResponse->getCurlErrorMessage());
            }
        } else {
            if (file_exists($wsdlPath)) {
                $document = $this->getXmlFileDOMDocument($curl, $cacheType, file_get_contents($wsdlPath));
                $this->saveXmlDOMDocument($document, $cacheFilePath);
            } else {
                throw new Exception('Could write WSDL cache file: local file does not exist: '.$wsdlPath);
            }
        }
    }

    private function getLocalWsdlPath($wsdlPath)
    {
        if (file_exists($wsdlPath)) {

            return realpath($wsdlPath);

        } else {
            throw new Exception('Could not download WSDL: local file does not exist: '.$wsdlPath);
        }
    }

    /**
     * @param string $wsdlPath File URL/path
     * @return boolean
     */
    private function isRemoteFile($wsdlPath)
    {
        $parsedUrlOrFalse = @parse_url($wsdlPath);
        if ($parsedUrlOrFalse !== false) {
            if (isset($parsedUrlOrFalse['scheme']) && substr($parsedUrlOrFalse['scheme'], 0, 4) === 'http') {

                return true;

            } else {

                return false;
            }
        }

        throw new Exception('Could not determine wsdlPath is remote: '.$wsdlPath);
    }

    /**
     * Resolves remote WSDL/XSD includes within the WSDL files.
     *
     * @param Curl $curl
     * @param int $cacheType
     * @param string  $xmlFileSource  XML file contents
     * @param boolean $parentFilePath Parent file name
     * @return DOMDocument
     */
    private function getXmlFileDOMDocument(Curl $curl, $cacheType, $xmlFileSource, $parentFilePath = null)
    {
        $document = new DOMDocument('1.0', 'utf-8');
        if ($document->loadXML($xmlFileSource) === false) {
            throw new Exception('Could not save downloaded WSDL cache: '.$xmlFileSource);
        }

        $xpath = new DOMXPath($document);
        $this->updateXmlDocument($curl, $cacheType, $xpath, Helper::PFX_WSDL, Helper::NS_WSDL, 'location', $parentFilePath);
        $this->updateXmlDocument($curl, $cacheType, $xpath, Helper::PFX_XML_SCHEMA, Helper::NS_XML_SCHEMA, 'schemaLocation', $parentFilePath);

        return $document;
    }

    private function saveXmlDOMDocument(DOMDocument $document, $cacheFilePath)
    {
        try {
            $xmlContents = $document->saveXML();
            if ($xmlContents === '') {
                throw new Exception('Could not write WSDL cache file: DOMDocument returned empty XML file');
            }
            file_put_contents($cacheFilePath, $xmlContents);
        } catch (Exception $e) {
            unlink($cacheFilePath);
            throw new Exception('Could not write WSDL cache file: save method returned error: ' . $e->getMessage());
        }
    }

    private function updateXmlDocument(
        Curl $curl,
        $cacheType,
        DOMXPath $xpath,
        $schemaPrefix,
        $schemaUrl,
        $locationAttributeName,
        $parentFilePath = null
    ) {
        $xpath->registerNamespace($schemaPrefix, $schemaUrl);
        $nodes = $xpath->query('.//'.$schemaPrefix.':include | .//'.$schemaPrefix.':import');
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                /** @var DOMElement $node */
                $locationPath = $node->getAttribute($locationAttributeName);
                if ($locationPath !== '') {
                    if ($this->isRemoteFile($locationPath)) {
                        $node->setAttribute(
                            $locationAttributeName,
                            $this->getWsdlPath(
                                $curl,
                                $locationPath,
                                $cacheType,
                                true
                            )
                        );
                    } else if ($parentFilePath !== null) {
                        $node->setAttribute(
                            $locationAttributeName,
                            $this->getWsdlPath(
                                $curl,
                                $this->resolveRelativePathInUrl($parentFilePath, $locationPath),
                                $cacheType,
                                true
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Resolves the relative path to base into an absolute.
     *
     * @param string $base     Base path
     * @param string $relative Relative path
     *
     * @return string
     */
    private function resolveRelativePathInUrl($base, $relative)
    {
        $urlParts = parse_url($base);

        // combine base path with relative path
        if (isset($urlParts['path']) && mb_strlen($relative) > 0 && '/' === $relative{0}) {
            // $relative is absolute path from domain (starts with /)
            $path = $relative;
        } elseif (isset($urlParts['path']) && strrpos($urlParts['path'], '/') === (strlen($urlParts['path']) )) {
            // base path is directory
            $path = $urlParts['path'].$relative;
        } elseif (isset($urlParts['path'])) {
            // strip filename from base path
            $path = substr($urlParts['path'], 0, strrpos($urlParts['path'], '/')).'/'.$relative;
        } else {
            // no base path
            $path = '/'.$relative;
        }

        // foo/./bar ==> foo/bar
        // remove double slashes
        $path = preg_replace(array('#/\./#', '#/+#'), '/', $path);

        // split path by '/'
        $parts = explode('/', $path);

        // resolve /../
        foreach ($parts as $key => $part) {
            if ($part === '..') {
                $keyToDelete = $key - 1;
                while ($keyToDelete > 0) {
                    if (isset($parts[$keyToDelete])) {
                        unset($parts[$keyToDelete]);

                        break;
                    }

                    $keyToDelete--;
                }

                unset($parts[$key]);
            }
        }

        $hostname = $urlParts['scheme'].'://'.$urlParts['host'];
        if (isset($urlParts['port'])) {
            $hostname .= ':'.$urlParts['port'];
        }

        return $hostname.implode('/', $parts);
    }
}
