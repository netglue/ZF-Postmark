<?php
declare(strict_types=1);

namespace NetgluePostmark\EventManager;

use DateTimeImmutable;
use NetgluePostmark\Exception;
use function array_key_exists;
use function in_array;

class BounceEvent extends OutboundEvent
{

    protected $name = self::EVENT_BOUNCE_OTHER;

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

    protected static $hardBounceCodes = [
        1,
        2048,
        8192,
        16384,
        100000,
        100002,
        100006,
        100007,
        100008,
        100009,
        100010,
    ];

    protected static $softBounceCodes = [
        2,
        128,
        256,
        4096,
    ];

    protected static $spamComplaintCodes = [
        512,
        100001,
    ];

    public static function withPayload(array $payload) : OutboundEvent
    {
        /** @var self $event */
        $event = parent::withPayload($payload);
        if (! isset($payload['TypeCode']) || ! array_key_exists($payload['TypeCode'], self::$postmarkBounceCodes)) {
            throw new Exception\InvalidArgumentException('No Bounce "TypeCode" can be detected in the payload');
        }
        if (! $event->isSpamComplaint() && $event->isHardBounce()) {
            $event->name = self::EVENT_HARD_BOUNCE;
        }
        if (! $event->isSpamComplaint() && $event->isSoftBounce()) {
            $event->name = self::EVENT_SOFT_BOUNCE;
        }
        return $event;
    }

    public function getBounceCode() :? int
    {
        $payload = $this->payload();
        return isset($payload['TypeCode']) ? $payload['TypeCode'] : null;
    }

    public function getRecipient() :? string
    {
        return $this->payloadPropertyToString('Email');
    }

    public function isHardBounce() : bool
    {
        return in_array($this->getBounceCode(), self::$hardBounceCodes, true);
    }

    public function isSoftBounce() : bool
    {
        return in_array($this->getBounceCode(), self::$softBounceCodes, true);
    }

    public function isSpamComplaint() : bool
    {
        return in_array($this->getBounceCode(), self::$spamComplaintCodes, true);
    }

    public function getBounceName() :? string
    {
        return $this->payloadPropertyToString('Name');
    }

    public function getDescription() :? string
    {
        return $this->payloadPropertyToString('Description');
    }

    public function getBounceDate() :? DateTimeImmutable
    {
        return $this->payloadPropertyToDateTime('BouncedAt');
    }
}
