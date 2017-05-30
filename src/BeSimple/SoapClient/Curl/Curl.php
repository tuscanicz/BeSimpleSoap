<?php

namespace BeSimple\SoapClient\Curl;

use BeSimple\SoapClient\Curl\Http\HttpAuthenticationBasicOptions;
use BeSimple\SoapClient\Curl\Http\HttpAuthenticationDigestOptions;
use BeSimple\SoapClient\Curl\Http\SslCertificateOptions;
use Exception;

class Curl
{
    const CURL_SUCCESS = true;
    const CURL_FAILED = false;

    private $curlSession;
    private $options;

    /**
     * @param CurlOptions $options
     */
    public function __construct(CurlOptions $options)
    {
        $this->curlSession = $this->acquireNewCurlSession($options);
        $this->options = $options;
    }

    public function __destruct()
    {
        $this->closeCurlSession($this->curlSession);
    }

    /**
     * @param string $location       HTTP location
     * @param string $request        Request body
     * @param array  $requestHeaders Request header strings
     * @return CurlResponse
     */
    public function executeCurlWithCachedSession($location, $request = null, $requestHeaders = [])
    {
        return $this->executeCurlSession($this->curlSession, $this->options, $location, $request, $requestHeaders);
    }

    /**
     * @param CurlOptions   $options
     * @param string        $location           HTTP location
     * @param string        $request            Request body
     * @param array         $requestHeaders     Request header strings
     * @return CurlResponse
     */
    public function executeCurl(CurlOptions $options, $location, $request = null, $requestHeaders = [])
    {
        $curlSession = $this->acquireNewCurlSession($options);
        $curlResponse = $this->executeCurlSession($curlSession, $options, $location, $request, $requestHeaders);
        $this->closeCurlSession($curlSession);

        return $curlResponse;
    }

    private function acquireNewCurlSession(CurlOptions $options)
    {
        $curlSession = curl_init();
        curl_setopt_array($curlSession, [
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER => true,
            CURLOPT_USERAGENT => $options->getUserAgent(),
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CONNECTTIMEOUT => $options->getConnectionTimeout()
        ]);

        return $curlSession;
    }

    private function closeCurlSession($curlSession)
    {
        curl_close($curlSession);
    }

    /**
     * @param mixed         $curlSession        Result of curl_init() handle
     * @param CurlOptions   $options
     * @param string        $location           HTTP location
     * @param string        $request            Request body
     * @param array         $requestHeaders     Request header strings
     * @return CurlResponse
     */
    private function executeCurlSession($curlSession, CurlOptions $options, $location, $request = null, $requestHeaders = [])
    {
        curl_setopt($curlSession, CURLOPT_URL, $location);
        curl_setopt($curlSession, CURLOPT_HEADER, true);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        if ($request !== null) {
            curl_setopt($curlSession, CURLOPT_POST, true);
            curl_setopt($curlSession, CURLOPT_POSTFIELDS, $request);
        } else {
            curl_setopt($curlSession, CURLOPT_POST, false);
        }
        if (count($requestHeaders) > 0) {
            curl_setopt($curlSession, CURLOPT_HTTPHEADER, $requestHeaders);
        }
        if (!$options->getSoapCompression() & SOAP_COMPRESSION_ACCEPT) {
            curl_setopt($curlSession, CURLOPT_ENCODING, 'identity');
        }
        if ($options->hasProxy()) {
            $proxyHost = $options->getProxy()->getHost() . $options->getProxy()->getPort();
            curl_setopt($curlSession, CURLOPT_PROXY, $proxyHost);
            if ($options->getProxy()->hasCredentials()) {
                curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $options->getProxy()->getLogin() . ':' . $options->getProxy()->getPassword());
                if ($options->getProxy()->hasAuthenticationType()) {
                    curl_setopt($curlSession, CURLOPT_PROXYAUTH, $options->getProxy()->getAuthenticationType());
                }
            }
        }
        if ($options->hasHttpAuthentication()) {
            if ($options->hasHttpAuthenticationBasic()) {
                /** @var HttpAuthenticationBasicOptions $httpAuthenticationBasic */
                $httpAuthenticationBasic = $options->getHttpAuthentication();
                curl_setopt($curlSession, CURLOPT_HTTPAUTH, $httpAuthenticationBasic->getAuthenticationType());
                curl_setopt($curlSession, CURLOPT_USERPWD, $httpAuthenticationBasic->getUsername() . ':' . $httpAuthenticationBasic->getPassword());
            } else if ($options->hasHttpAuthenticationDigest()) {
                /** @var HttpAuthenticationDigestOptions $httpAuthenticationDigest */
                $httpAuthenticationDigest = $options->getHttpAuthentication();
                curl_setopt($curlSession, CURLOPT_HTTPAUTH, $httpAuthenticationDigest->getAuthenticationType());
            } else {
                throw new Exception('Unresolved authentication type: '.get_class($options->getHttpAuthentication()));
            }
        }
        if ($options->hasSslCertificateOptions()) {
            $sslCertificateOptions = $options->getSslCertificateOptions();
            curl_setopt($curlSession, CURLOPT_SSLCERT, $sslCertificateOptions->getCertificateLocalPath());
            if ($sslCertificateOptions->hasCertificatePassPhrase()) {
                curl_setopt($curlSession, CURLOPT_SSLCERTPASSWD, $sslCertificateOptions->getCertificatePassPhrase());
            }
            if ($sslCertificateOptions->hasCertificateAuthorityInfo()) {
                curl_setopt($curlSession, CURLOPT_CAINFO, $sslCertificateOptions->getCertificateAuthorityInfo());
            }
            if ($sslCertificateOptions->hasCertificateAuthorityPath()) {
                curl_setopt($curlSession, CURLOPT_CAPATH, $sslCertificateOptions->hasCertificateAuthorityPath());
            }
        }
        $executeSoapCallResponse = $this->executeHttpCall($curlSession, $options);

        $httpRequestHeadersAsString = curl_getinfo($curlSession, CURLINFO_HEADER_OUT);
        $headerSize = curl_getinfo($curlSession, CURLINFO_HEADER_SIZE);
        $httpResponseCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
        $httpResponseContentType = curl_getinfo($curlSession, CURLINFO_CONTENT_TYPE);;
        $responseBody = substr($executeSoapCallResponse, $headerSize);
        $responseHeaders = substr($executeSoapCallResponse, 0, $headerSize);
        preg_match('/HTTP\/(1\.[0-1]+) ([0-9]{3}) (.*)/', $executeSoapCallResponse, $httpResponseMessages);
        $httpResponseMessage = trim(array_pop($httpResponseMessages));
        $curlErrorMessage = sprintf(
            'Curl error "%s" with message: %s occurred while connecting to %s with HTTP response code %s',
            curl_errno($curlSession),
            curl_error($curlSession),
            $location,
            $httpResponseCode
        );

        if (!is_integer($httpResponseCode) || $httpResponseCode >= 400 || $httpResponseCode === 0) {

            return new CurlResponse(
                $this->normalizeStringOrFalse($httpRequestHeadersAsString),
                $httpResponseCode,
                $httpResponseMessage,
                $httpResponseContentType,
                self::CURL_FAILED,
                $this->normalizeStringOrFalse($responseHeaders),
                $this->normalizeStringOrFalse($responseBody),
                $curlErrorMessage
            );
        }

        return new CurlResponse(
            $this->normalizeStringOrFalse($httpRequestHeadersAsString),
            $httpResponseCode,
            $httpResponseMessage,
            $httpResponseContentType,
            self::CURL_SUCCESS,
            $this->normalizeStringOrFalse($responseHeaders),
            $this->normalizeStringOrFalse($responseBody)
        );
    }

    /**
     * Custom curl_exec wrapper that allows to follow redirects when specific
     * http response code is set. SOAP only allows 307.
     *
     * @param mixed         $curlSession        Result of curl_init() handle
     * @param CurlOptions   $options
     * @param int           $executedRedirects
     * @return string|null
     * @throws Exception
     */
    private function executeHttpCall($curlSession, CurlOptions $options, $executedRedirects = 0)
    {
        if ($executedRedirects > $options->getFollowLocationMaxRedirects()) {
            throw new Exception('Cannot executeHttpCall - too many redirects: ' . $executedRedirects);
        }
        $curlExecResponse = curl_exec($curlSession);
        $httpResponseCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
        if ($httpResponseCode === 307) {
            $newUrl = $this->getRedirectUrlFromResponseHeaders($curlSession, $curlExecResponse);
            curl_setopt($curlSession, CURLOPT_URL, $newUrl);

            return $this->executeHttpCall($curlSession, $options, ++$executedRedirects);
        }

        return $curlExecResponse;
    }

    private function getRedirectUrlFromResponseHeaders($curlSession, $curlExecResponse)
    {
        $curlExecResponseHeaders = substr($curlExecResponse, 0, curl_getinfo($curlSession, CURLINFO_HEADER_SIZE));
        $matches = [];
        preg_match('/Location:(.*?)\n/', $curlExecResponseHeaders, $matches);
        $url = trim(array_pop($matches));

        if (($url = @parse_url($url)) !== false) {
            $lastUrl = parse_url(curl_getinfo($curlSession, CURLINFO_EFFECTIVE_URL));
            if (!isset($url['scheme'])) {
                $url['scheme'] = $lastUrl['scheme'];
            }
            if (!isset($url['host'])) {
                $url['host'] = $lastUrl['host'];
            }
            if (!isset($url['path'])) {
                $url['path'] = $lastUrl['path'];
            }

            return $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query'] ? '?' . $url['query'] : '');
        }

        throw new Exception('Cannot parse WSDL url redirect: ' . $url);
    }

    private function normalizeStringOrFalse($string)
    {
        if ($string === false || $string === '') {
            $string = null;
        }

        return $string;
    }
}
