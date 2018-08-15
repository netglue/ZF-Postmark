<?php
declare(strict_types=1);

namespace NetgluePostmark\Listener;

use NetgluePostmark\EventManager\BounceEvent;
use NetgluePostmark\EventManager\ClickEvent;
use NetgluePostmark\EventManager\DeliveryEvent;
use NetgluePostmark\EventManager\InboundMessageEvent;
use NetgluePostmark\EventManager\OpenEvent;
use NetgluePostmark\EventManager\OutboundEvent;
use Psr\Log\LoggerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

class LoggingListener extends AbstractListenerAggregate
{

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_SOFT_BOUNCE, [$this, 'onBounce']);
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_HARD_BOUNCE, [$this, 'onBounce']);
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_BOUNCE_OTHER, [$this, 'onBounce']);
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_SPAM_COMPLAINT, [$this, 'onBounce']);
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_CLICK, [$this, 'onClick']);
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_OPEN, [$this, 'onOpen']);
        $this->listeners[] = $events->attach(OutboundEvent::EVENT_DELIVERY, [$this, 'onDelivery']);
        $this->listeners[] = $events->attach(InboundMessageEvent::EVENT_INBOUND, [$this, 'onEmailReceived']);
    }

    public function onClick(ClickEvent $event)
    {
        $message = sprintf(
            'An email sent to %s just received a click',
            $event->getRecipient()
        );
        $this->logger->info(
            $message,
            $event->payload()
        );
    }

    public function onOpen(OpenEvent $event)
    {
        $message = sprintf(
            'An email sent to %s was just opened',
            $event->getRecipient()
        );
        $this->logger->info(
            $message,
            $event->payload()
        );
    }

    public function onDelivery(DeliveryEvent $event)
    {
        $message = sprintf(
            'An email for %s was just successfully delivered',
            $event->getRecipient()
        );
        $this->logger->info(
            $message,
            $event->payload()
        );
    }

    public function onEmailReceived(InboundMessageEvent $event)
    {
        $message = sprintf(
            'An email from %s was just received',
            $event->getSenderEmail()
        );
        $this->logger->info(
            $message,
            $event->payload()
        );
    }

    public function onBounce(BounceEvent $event)
    {
        $this->logger->warning(
            $this->formatBounceMessage($event),
            $event->payload()
        );
    }

    private function formatBounceMessage(BounceEvent $event) : string
    {
        $bounceType = 'Other';
        $bounceType = $event->isHardBounce() ? 'Hard' : $bounceType;
        $bounceType = $event->isSoftBounce() ? 'Soft' : $bounceType;
        $bounceType = $event->isSpamComplaint() ? 'Spam Complaint' : $bounceType;
        return sprintf(
            'An email message addressed to %s just bounced with code %d (%s Bounce)',
            $event->getRecipient(),
            $event->getBounceCode(),
            $bounceType
        );
    }
}
