<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

use NetgluePostmark\Exception;
use function array_key_exists;

abstract class OutboundEvent extends AbstractEvent
{

    protected static $recordTypes = [
        'Bounce'        => BounceEvent::class,
        'Open'          => OpenEvent::class,
        'Delivery'      => DeliveryEvent::class,
        'Click'         => ClickEvent::class,
        'SpamComplaint' => SpamComplaintEvent::class,
    ];

    final public static function factory(string $jsonPayload) : self
    {
        $payload = self::payloadFromString($jsonPayload);
        if (! isset($payload['RecordType'])) {
            throw new Exception\InvalidArgumentException('The given payload does not contain a "RecordType" property');
        }
        if (! array_key_exists($payload['RecordType'], self::$recordTypes)) {
            throw new Exception\UnknownEventTypeException(sprintf(
                'Unknown event type "%s"',
                $payload['RecordType']
            ));
        }
        $eventClass = self::$recordTypes[$payload['RecordType']];
        /** @var OutboundEvent $event */
        $event = $eventClass::withPayload($payload);
        return $event;
    }

    protected static function withPayload(array $payload) : self
    {
        $event = new static;
        $event->payload = $payload;
        $event->setParam('payload', $payload);
        return $event;
    }

    public function getRecipient() :? string
    {
        $payload = $this->payload();
        return isset($payload['Recipient']) ? $payload['Recipient'] : null;
    }
}
