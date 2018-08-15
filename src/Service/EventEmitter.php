<?php
declare(strict_types=1);

namespace NetgluePostmark\Service;

use NetgluePostmark\EventManager\InboundMessageEvent;
use NetgluePostmark\EventManager\OutboundEvent;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;

class EventEmitter implements EventsCapableInterface
{

    /** @var EventManager */
    private $eventManager;

    public function __construct()
    {
        $this->eventManager = new EventManager();
        $this->eventManager->setIdentifiers([__CLASS__]);
    }

    public function getEventManager()
    {
        return $this->eventManager;
    }

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
