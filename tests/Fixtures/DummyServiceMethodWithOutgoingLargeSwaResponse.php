<?php

namespace Fixtures;

use Fixtures\Attachment\MessageWithAttachmentsTrait;

class DummyServiceMethodWithOutgoingLargeSwaResponse
{
    use MessageWithAttachmentsTrait;

    /**
     * @var bool $status
     */
    public $status;
}
