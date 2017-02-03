<?php

namespace BeSimple\SoapServer\Tests\Attachment;

class AttachmentCollection
{
    /**
     * @var Attachment[] $attachments
     */
    public $attachments;

    public function __construct(array $attachments = null)
    {
        $this->attachments = $attachments;
    }

    public function hasAttachments()
    {
        return $this->attachments !== null && count($this->attachments) > 0;
    }
}
