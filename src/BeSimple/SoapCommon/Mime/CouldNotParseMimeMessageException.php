<?php

namespace BeSimple\SoapCommon\Mime;

use Exception;

class CouldNotParseMimeMessageException extends Exception
{
    private $mimePartMessage;
    private $headers;

    public function __construct($message, $mimePartMessage, array $headers)
    {
        $this->mimePartMessage = $mimePartMessage;
        $this->headers = $headers;
        parent::__construct($message);
    }

    public function getMimePartMessage()
    {
        return $this->mimePartMessage;
    }

    public function hasHeaders()
    {
        return count($this->headers) > 0;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
