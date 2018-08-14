<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

class DeliveryEvent extends OutboundEvent
{
    protected $name = self::EVENT_DELIVERY;
}
