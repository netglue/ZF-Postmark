<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

class SpamComplaintEvent extends BounceEvent
{
    protected $name = self::EVENT_SPAM_COMPLAINT;
}
