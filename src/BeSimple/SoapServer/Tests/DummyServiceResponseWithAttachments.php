<?php

namespace BeSimple\SoapServer\Tests;

use BeSimple\SoapServer\Tests\Attachment\AttachmentCollection;

class DummyServiceResponseWithAttachments
{
    /**
     * @var bool $status
     */
    public $status;

    /**
     * @var AttachmentCollection $attachmentCollection
     */
    public $attachmentCollection;

    public function hasAttachments()
    {
        return $this->attachmentCollection !== null && $this->attachmentCollection->hasAttachments();
    }
}
