<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

class ClickEvent extends OutboundEvent
{
    protected $name = self::EVENT_CLICK;
}
