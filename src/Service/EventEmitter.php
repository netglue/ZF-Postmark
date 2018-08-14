<?php
declare(strict_types=1);

namespace NetgluePostmark\Service;

use NetgluePostmark\EventManager\PostmarkEvent;
use NetgluePostmark\Exception;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use function array_key_exists;
use function in_array;
use function json_decode;

class EventEmitter implements EventManagerAwareInterface
{

    use EventManagerAwareTrait;

    public const EVENT_HARD_BOUNCE  = 'postmark.event.hard_bounce';
    public const EVENT_SOFT_BOUNCE  = 'postmark.event.soft_bounce';
    public const EVENT_BOUNCE_OTHER = 'postmark.event.soft_bounce';
    public const EVENT_OPEN         = 'postmark.event.open';
    public const EVENT_CLICK        = 'postmark.event.click';
    public const EVENT_DELIVERY     = 'postmark.event.delivery';
    public const EVENT_COMPLAINT    = 'postmark.event.complaint';

    private static $postmarkRecordTypes = [
        'Bounce'        => null,
        'Open'          => self::EVENT_OPEN,
        'Delivery'      => self::EVENT_DELIVERY,
        'Click'         => self::EVENT_CLICK,
        'SpamComplaint' => self::EVENT_COMPLAINT,
    ];

    private static $postmarkBounceCodes = [
        1      => 'HardBounce',
        2      => 'Transient',
        16     => 'Unsubscribe',
        32     => 'Subscribe',
        64     => 'AutoResponder',
        128    => 'AddressChange',
        256    => 'DnsError',
        512    => 'SpamNotification',
        1024   => 'OpenRelayTest',
        2048   => 'Unknown',
        4096   => 'SoftBounce',
        8192   => 'VirusNotification',
        16384  => 'ChallengeVerification',
        100000 => 'BadEmailAddress',
        100001 => 'SpamComplaint',
        100002 => 'ManuallyDeactivated',
        100003 => 'Unconfirmed',
        100006 => 'Blocked',
        100007 => 'SMTPApiError',
        100008 => 'InboundError',
        100009 => 'DMARCPolicy',
        100010 => 'TemplateRenderingFailed',
    ];

    private static $hardBounceCodes = [
        1,
        2048,
        8192,
        16384,
        100000,
        100002,
        100006,
        100007,
        100009,
    ];

    private static $softBounceCodes = [
        2,
        128,
        256,
        512,
        4096,
    ];

    public function __construct()
    {
        $events = $this->getEventManager();
        $events->setEventPrototype(new PostmarkEvent());
    }

    public function process(string $jsonPayload) : void
    {
        $payload = json_decode($jsonPayload, true);
        if (! $payload) {
            throw new Exception\InvalidArgumentException('Event payload could not be decoded');
        }

        $type = isset($payload['RecordType'])
            ? $payload['RecordType']
            : 'unknown';
        $this->assertKnownRecordType($type);

        $eventName = $this->resolveEventName($payload);

        $this->getEventManager()->trigger(
            $eventName,
            $this,
            ['payload' => $payload]
        );
    }

    private function assertKnownRecordType(string $type) : void
    {
        if (! array_key_exists($type, self::$postmarkRecordTypes)) {
            throw new Exception\UnknownEventTypeException(sprintf(
                '%s is not a known event record type',
                $type
            ));
        }
    }

    private function resolveEventName(array $payload) : string
    {
        if ($payload['RecordType'] === 'Bounce') {
            $code = $this->getBounceCodeFromPayload($payload);
            if (in_array($code, self::$hardBounceCodes)) {
                return self::EVENT_HARD_BOUNCE;
            }
            if (in_array($code, self::$softBounceCodes)) {
                return self::EVENT_SOFT_BOUNCE;
            }
            return self::EVENT_BOUNCE_OTHER;
        }
        return self::$postmarkRecordTypes[$payload['RecordType']];
    }

    private function getBounceCodeFromPayload(array $payload) : int
    {
        $code = isset($payload['TypeCode']) ? $payload['TypeCode'] : null;
        if (! $code ) {
            throw new Exception\RuntimeException(
                'Received an empty bounce code'
            );
        }
        if (! in_array($code, self::$postmarkBounceCodes, true)) {
            throw new Exception\RuntimeException(sprintf(
                'The code %d is not a valid bounce code',
                $code
            ));
        }
        /** @var int $code */
        return $code;
    }
}
