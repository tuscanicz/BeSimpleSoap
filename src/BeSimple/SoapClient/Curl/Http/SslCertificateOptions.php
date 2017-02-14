<?php

namespace BeSimple\SoapClient\Curl\Http;

class SslCertificateOptions
{
    private $certificateLocalPath;
    private $certificatePassPhrase;
    private $certificateAuthorityInfo;
    private $certificateAuthorityPath;

    /**
     * @param string $certificateLocalPath
     * @param string $certificatePassPhrase
     * @param string $certificateAuthorityInfo
     * @param string $certificateAuthorityPath
     */
    public function __construct(
        $certificateLocalPath,
        $certificatePassPhrase = null,
        $certificateAuthorityInfo = null,
        $certificateAuthorityPath = null
    ) {
        $this->certificateLocalPath = $certificateLocalPath;
        $this->certificatePassPhrase = $certificatePassPhrase;
        $this->certificateAuthorityInfo = $certificateAuthorityInfo;
        $this->certificateAuthorityPath = $certificateAuthorityPath;
    }

    public function getCertificateLocalPath()
    {
        return $this->certificateLocalPath;
    }

    public function getCertificatePassPhrase()
    {
        return $this->certificatePassPhrase;
    }

    public function getCertificateAuthorityInfo()
    {
        return $this->certificateAuthorityInfo;
    }

    public function getCertificateAuthorityPath()
    {
        return $this->certificateAuthorityPath;
    }

    public function hasCertificatePassPhrase()
    {
        return $this->certificatePassPhrase !== null;
    }

    public function hasCertificateAuthorityInfo()
    {
        return $this->certificateAuthorityInfo !== null;
    }

    public function hasCertificateAuthorityPath()
    {
        return $this->certificateAuthorityPath !== null;
    }
}
