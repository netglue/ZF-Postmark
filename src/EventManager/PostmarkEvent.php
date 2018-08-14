<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

use NetgluePostmark\Exception;
use NetgluePostmark\Service\EventEmitter;
use Zend\EventManager\Event;

class PostmarkEvent extends Event
{

    public function setPayload(array $payload) : void
    {
        $this->setParam('payload', $payload);
    }

    public function getPayload() :? array
    {
        return $this->getParam('payload', null);
    }

    private function assertPayload() : array
    {
        if (! $this->getPayload()) {
            throw new Exception\RuntimeException('No payload is available in this event');
        }
    }

    public function isHardBounce() : bool
    {
        return $this->getName() === EventEmitter::EVENT_HARD_BOUNCE;
    }

    public function isSoftBounce() : bool
    {
        return $this->getName() === EventEmitter::EVENT_SOFT_BOUNCE;
    }
}
