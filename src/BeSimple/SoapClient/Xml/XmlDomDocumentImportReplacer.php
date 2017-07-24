<?php

namespace BeSimple\SoapClient\Xml;

use BeSimple\SoapClient\Curl\Curl;
use BeSimple\SoapClient\WsdlDownloader;
use BeSimple\SoapClient\Xml\Path\RelativePathResolver;
use DOMElement;
use DOMXPath;

class XmlDomDocumentImportReplacer
{
    public static function instantiateReplacer()
    {
        return new self();
    }

    public function updateXmlDocument(
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
                    if (RemoteFileResolver::instantiateResolver()->isRemoteFile($locationPath)) {
                        $node->setAttribute(
                            $locationAttributeName,
                            WsdlDownloader::instantiateDownloader()->getWsdlPath(
                                $curl,
                                $locationPath,
                                $cacheType,
                                true
                            )
                        );
                    } elseif ($parentFilePath !== null) {
                        $node->setAttribute(
                            $locationAttributeName,
                            WsdlDownloader::instantiateDownloader()->getWsdlPath(
                                $curl,
                                RelativePathResolver::instantiateResolver()
                                    ->resolveRelativePathInUrl($parentFilePath, $locationPath),
                                $cacheType,
                                true
                            )
                        );
                    }
                }
            }
        }
    }
}
