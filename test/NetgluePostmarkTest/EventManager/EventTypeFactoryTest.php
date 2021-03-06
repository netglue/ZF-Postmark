<?php
declare(strict_types=1);

namespace NetgluePostmarkTest\EventManager;

use DateTimeImmutable;
use NetgluePostmark\EventManager\AbstractEvent;
use NetgluePostmark\EventManager\BounceEvent;
use NetgluePostmark\EventManager\ClickEvent;
use NetgluePostmark\EventManager\DeliveryEvent;
use NetgluePostmark\EventManager\InboundMessageEvent;
use NetgluePostmark\EventManager\OpenEvent;
use NetgluePostmark\EventManager\OutboundEvent;
use NetgluePostmark\EventManager\SpamComplaintEvent;
use NetgluePostmarkTest\TestCase;

class EventTypeFactoryTest extends TestCase
{

    private $expectedMessageId = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

    private $expectedRecipient = 'john@example.com';

    private function assertOutboundStandardProperties(OutboundEvent $event)
    {
        $this->assertSame($this->expectedRecipient, $event->getRecipient());
        $this->assertSame($this->expectedMessageId, $event->getMessageId());
    }

    public function testBounceEvent()
    {
        /** @var BounceEvent $event */
        $event = OutboundEvent::factory($this->getJsonFixture('bounce.json'));
        $this->assertInstanceOf(BounceEvent::class, $event);
        $this->assertTrue($event->isHardBounce());
        $this->assertFalse($event->isSoftBounce());
        $this->assertFalse($event->isSpamComplaint());
        $this->assertSame(AbstractEvent::EVENT_HARD_BOUNCE, $event->getName());
        $this->assertOutboundStandardProperties($event);
        $this->assertNotNull($event->getDescription());
        $this->assertSame('Hard bounce', $event->getBounceName());
    }

    public function testSoftBounceEvent()
    {
        /** @var BounceEvent $event */
        $event = OutboundEvent::factory($this->getJsonFixture('soft-bounce.json'));
        $this->assertInstanceOf(BounceEvent::class, $event);
        $this->assertFalse($event->isHardBounce());
        $this->assertTrue($event->isSoftBounce());
        $this->assertFalse($event->isSpamComplaint());
        $this->assertSame(AbstractEvent::EVENT_SOFT_BOUNCE, $event->getName());
        $this->assertOutboundStandardProperties($event);
        $this->assertNotNull($event->getDescription());
        $date = $event->getBounceDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('1/8/2014', $date->format('j/n/Y'));
        $this->assertSame('-04:00', $date->format('P'));
        $this->assertSame('13:28:10', $date->format('H:i:s'));
        $this->assertSame('Soft bounce', $event->getBounceName());
    }

    public function testClickEvent()
    {
        /** @var ClickEvent $event */
        $event = OutboundEvent::factory($this->getJsonFixture('click.json'));
        $this->assertInstanceOf(ClickEvent::class, $event);
        $this->assertOutboundStandardProperties($event);
        $this->assertNotNull($event->getClickedUrl());
    }

    public function testDeliveryEvent()
    {
        /** @var DeliveryEvent $event */
        $event = OutboundEvent::factory($this->getJsonFixture('delivery.json'));
        $this->assertInstanceOf(DeliveryEvent::class, $event);
        $this->assertOutboundStandardProperties($event);
        $date = $event->getDeliveryDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('1/8/2014', $date->format('j/n/Y'));
        $this->assertSame('-04:00', $date->format('P'));
        $this->assertSame('13:28:10', $date->format('H:i:s'));
    }

    public function testInboundEvent()
    {
        $event = InboundMessageEvent::factory($this->getJsonFixture('inbound.json'));
        $this->assertInstanceOf(InboundMessageEvent::class, $event);
        $this->assertSame('Postmarkapp Support', $event->getSenderName());
        $this->assertSame('support@postmarkapp.com', $event->getSenderEmail());
    }

    public function testOpenEvent()
    {
        $event = OutboundEvent::factory($this->getJsonFixture('open.json'));
        $this->assertInstanceOf(OpenEvent::class, $event);
        $this->assertOutboundStandardProperties($event);
    }

    public function testSpamComplaintEvent()
    {
        /** @var BounceEvent $event */
        $event = OutboundEvent::factory($this->getJsonFixture('spam-complaint.json'));
        $this->assertInstanceOf(SpamComplaintEvent::class, $event);
        $this->assertFalse($event->isHardBounce());
        $this->assertFalse($event->isSoftBounce());
        $this->assertTrue($event->isSpamComplaint());
        $this->assertSame(AbstractEvent::EVENT_SPAM_COMPLAINT, $event->getName());
        $this->assertOutboundStandardProperties($event);
        $this->assertNull($event->getDescription());
    }

    /**
     * @expectedException \NetgluePostmark\Exception\InvalidArgumentException
     * @expectedExceptionMessage No Bounce "TypeCode" can be detected in the payload
     */
    public function testBounceWithoutTypeCodeIsExceptional()
    {
        $jsonString = $this->getJsonFixture('bounce.json');
        $data = \json_decode($jsonString, true);
        unset($data['TypeCode']);
        OutboundEvent::factory(\json_encode($data));
    }

    /**
     * @expectedException \NetgluePostmark\Exception\DomainException
     * @expectedExceptionMessage This event has no payload
     */
    public function testMissingPayloadIsExceptional()
    {
        $event = new BounceEvent();
        $event->getRecipient();
    }

    /**
     * @expectedException \NetgluePostmark\Exception\InvalidArgumentException
     * @expectedExceptionMessage The given payload does not contain a "RecordType" property
     */
    public function testMissingRecordTypeIsExceptional()
    {
        $jsonString = $this->getJsonFixture('bounce.json');
        $data = \json_decode($jsonString, true);
        unset($data['RecordType']);
        OutboundEvent::factory(\json_encode($data));
    }

    /**
     * @expectedException \NetgluePostmark\Exception\UnknownEventTypeException
     * @expectedExceptionMessage Unknown event type "Unknown"
     */
    public function testUnknownRecordTypeIsExceptional()
    {
        $jsonString = $this->getJsonFixture('bounce.json');
        $data = \json_decode($jsonString, true);
        $data['RecordType'] = 'Unknown';
        OutboundEvent::factory(\json_encode($data));
    }

    public function testInvalidDateFormatWillResultInNullInsteadOfException()
    {
        $jsonString = $this->getJsonFixture('soft-bounce.json');
        $data = \json_decode($jsonString, true);
        $data['BouncedAt'] = 'Invalid Date Format';
        /** @var BounceEvent $event */
        $event = $event = OutboundEvent::factory(\json_encode($data));
        $this->assertInstanceOf(BounceEvent::class, $event);
        $this->assertNull($event->getBounceDate());
    }

    public function testEmptyStringBounceDateWillEvaluateToNull()
    {
        $jsonString = $this->getJsonFixture('soft-bounce.json');
        $data = \json_decode($jsonString, true);
        $data['BouncedAt'] = '';
        /** @var BounceEvent $event */
        $event = $event = OutboundEvent::factory(\json_encode($data));
        $this->assertInstanceOf(BounceEvent::class, $event);
        $this->assertNull($event->getBounceDate());
    }
}
