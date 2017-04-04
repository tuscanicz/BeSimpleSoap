<?php

namespace Fixtures\Attachment;

trait MessageWithAttachmentsTrait
{
    /**
     * @var AttachmentCollection $attachmentCollection
     */
    public $attachmentCollection;

    public function hasAttachments()
    {
        return $this->attachmentCollection !== null && $this->attachmentCollection->hasAttachments();
    }
}
