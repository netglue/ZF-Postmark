<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

use DateTimeImmutable;
use NetgluePostmark\Exception;
use Throwable;
use Zend\EventManager\Event;
use function json_decode;
use function is_array;

abstract class AbstractEvent extends Event
{
    public const EVENT_HARD_BOUNCE    = 'postmark.event.hard_bounce';
    public const EVENT_SOFT_BOUNCE    = 'postmark.event.soft_bounce';
    public const EVENT_BOUNCE_OTHER   = 'postmark.event.bounce_other';
    public const EVENT_OPEN           = 'postmark.event.open';
    public const EVENT_CLICK          = 'postmark.event.click';
    public const EVENT_DELIVERY       = 'postmark.event.delivery';
    public const EVENT_SPAM_COMPLAINT = 'postmark.event.spam_complaint';
    public const EVENT_INBOUND        = 'postmark.event.inbound';

    /** @var null|array */
    protected $payload;

    public function payload() : array
    {
        return $this->assertArrayPayload();
    }

    public function getMessageId() :? string
    {
        $payload = $this->payload();
        return isset($payload['MessageID']) ? $payload['MessageID'] : null;
    }

    private function assertArrayPayload() : array
    {
        if (! is_array($this->payload)) {
            throw new Exception\DomainException('This event has no payload');
        }
        return $this->payload;
    }

    protected static function payloadFromString(string $jsonPayload) : array
    {
        $payload = json_decode($jsonPayload, true);
        if (! $payload) {
            throw new Exception\InvalidArgumentException('Event payload could not be decoded');
        }
        return $payload;
    }

    protected function payloadPropertyToDateTime(string $propertyName) :? DateTimeImmutable
    {
        $dateString = $this->payloadPropertyToString($propertyName);
        if (! $dateString) {
            return null;
        }
        try {
            return new DateTimeImmutable($dateString);
        } catch (Throwable $exception) {
            return null;
        }
    }

    protected function payloadPropertyToString(string $propertyName) :? string
    {
        $payload = $this->payload();
        $value = isset($payload[$propertyName]) ? $payload[$propertyName] : null;
        return empty($value) ? null : (string) $value;
    }
}
