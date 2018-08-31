<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

use DateTimeImmutable;

class DeliveryEvent extends OutboundEvent
{
    protected $name = self::EVENT_DELIVERY;

    public function getDeliveryDate() :? DateTimeImmutable
    {
        return $this->payloadPropertyToDateTime('DeliveredAt');
    }
}
