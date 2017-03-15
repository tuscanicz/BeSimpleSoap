<?php

namespace Fixtures\Attachment;

class Attachment
{
    /**
     * @var string $fileName
     */
    public $fileName;

    /**
     * @var string $content
     */
    public $contentType;

    /**
     * @var string $content
     */
    public $content;

    /**
     * Attachment constructor.
     *
     * @param string $fileName
     * @param string $contentType
     * @param string $content
     */
    public function __construct($fileName, $contentType, $content)
    {
        $this->fileName = $fileName;
        $this->contentType = $contentType;
        $this->content = $content;
    }
}
