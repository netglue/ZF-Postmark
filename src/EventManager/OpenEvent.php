<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

class OpenEvent extends OutboundEvent
{
    protected $name = self::EVENT_OPEN;
}
