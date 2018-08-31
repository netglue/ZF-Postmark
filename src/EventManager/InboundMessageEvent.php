<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

class InboundMessageEvent extends AbstractEvent
{

    protected $name = self::EVENT_INBOUND;

    public static function factory(string $jsonPayload) : self
    {
        $payload = self::payloadFromString($jsonPayload);
        /** @var InboundMessageEvent $event */
        $event = new self;
        $event->payload = $payload;
        $event->setParam('payload', $payload);

        return $event;
    }

    public function getSenderName() :? string
    {
        return $this->payloadPropertyToString('FromName');
    }

    public function getSenderEmail() :? string
    {
        return $this->payloadPropertyToString('From');
    }
}
