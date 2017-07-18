<?php

namespace BeSimple\SoapClient\Xml;

use BeSimple\SoapClient\Curl\Curl;
use BeSimple\SoapClient\WsdlDownloader;
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
                                self::resolveRelativePathInUrl($parentFilePath, $locationPath),
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
    private static function resolveRelativePathInUrl($base, $relative)
    {
        $urlParts = parse_url($base);
        $isRelativePathAbsolute = 0 === strpos($relative, '/') || 0 === strpos($relative, '..');

        // combine base path with relative path
        if (isset($urlParts['path']) && mb_strlen($relative) > 0 && $isRelativePathAbsolute) {
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
        if (substr($hostname, -1) !== '/') {
            $hostname .= '/';
        }

        return $hostname.implode('/', $parts);
    }
}
