<?php

namespace Fixtures;

use Fixtures\Attachment\MessageWithAttachmentsTrait;

class DummyServiceMethodWithIncomingLargeSwaRequest
{
    use MessageWithAttachmentsTrait;

    /**
     * @var int $dummyAttribute
     */
    public $dummyAttribute;
}
