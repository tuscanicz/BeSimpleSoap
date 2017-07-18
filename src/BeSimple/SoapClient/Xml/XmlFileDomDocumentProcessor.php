<?php

namespace BeSimple\SoapClient\Xml;

use BeSimple\SoapClient\Curl\Curl;
use BeSimple\SoapCommon\Helper;
use DOMDocument;
use DOMXPath;
use Exception;

class XmlFileDomDocumentProcessor
{
    public static function writeCacheFile(Curl $curl, $cacheType, $wsdlPath, $cacheFilePath, $resolveRemoteIncludes, $isRemoteFile)
    {
        if ($isRemoteFile === true) {
            $curlResponse = $curl->executeCurlWithCachedSession($wsdlPath);
            if ($curlResponse->curlStatusSuccess()) {
                if (mb_strlen($curlResponse->getResponseBody()) === 0) {
                    throw new Exception('Could not write WSDL cache file: empty curl response from: '.$wsdlPath);
                }
                if ($resolveRemoteIncludes === true) {
                    $document = self::getXmlFileDomDocument($curl, $cacheType, $curlResponse->getResponseBody(), $wsdlPath);
                    self::saveXmlDomDocument($document, $cacheFilePath);
                } else {
                    file_put_contents($cacheFilePath, $curlResponse->getResponseBody());
                }
            } else {
                throw new Exception('Could not write WSDL cache file: Download failed with message: '.$curlResponse->getCurlErrorMessage());
            }
        } else {
            if (file_exists($wsdlPath)) {
                $document = self::getXmlFileDomDocument($curl, $cacheType, file_get_contents($wsdlPath));
                self::saveXmlDomDocument($document, $cacheFilePath);
            } else {
                throw new Exception('Could write WSDL cache file: local file does not exist: '.$wsdlPath);
            }
        }
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
    private static function getXmlFileDomDocument(Curl $curl, $cacheType, $xmlFileSource, $parentFilePath = null)
    {
        $document = new DOMDocument('1.0', 'utf-8');
        if ($document->loadXML($xmlFileSource) === false) {
            throw new Exception('Could not save downloaded WSDL cache: '.$xmlFileSource);
        }

        $xpath = new DOMXPath($document);
        $xmlDomDocumentImportReplacer = XmlDomDocumentImportReplacer::instantiateReplacer();
        $xmlDomDocumentImportReplacer->updateXmlDocument($curl, $cacheType, $xpath, Helper::PFX_WSDL, Helper::NS_WSDL, 'location', $parentFilePath);
        $xmlDomDocumentImportReplacer->updateXmlDocument($curl, $cacheType, $xpath, Helper::PFX_XML_SCHEMA, Helper::NS_XML_SCHEMA, 'schemaLocation', $parentFilePath);

        return $document;
    }

    private static function saveXmlDomDocument(DOMDocument $document, $cacheFilePath)
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
}
