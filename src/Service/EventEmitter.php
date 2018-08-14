<?php
declare(strict_types=1);

namespace NetgluePostmark\Service;

use NetgluePostmark\EventManager\InboundMessageEvent;
use NetgluePostmark\EventManager\OutboundEvent;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

class EventEmitter implements EventManagerAwareInterface
{

    use EventManagerAwareTrait;

    public function process(string $jsonPayload) : void
    {
        $event = OutboundEvent::factory($jsonPayload);
        $event->setTarget($this);

        $this->getEventManager()->triggerEvent($event);
    }

    public function processInbound(string $jsonPayload) : void
    {
        $event = InboundMessageEvent::factory($jsonPayload);
        $event->setTarget($this);

        $this->getEventManager()->triggerEvent($event);
    }
}
