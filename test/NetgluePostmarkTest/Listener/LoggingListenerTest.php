<?php
declare(strict_types=1);

namespace NetgluePostmarkTest\Listener;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use NetgluePostmark\Container\Listener\LoggingListenerDelegatorFactory;
use NetgluePostmark\EventManager\BounceEvent;
use NetgluePostmark\EventManager\ClickEvent;
use NetgluePostmark\EventManager\DeliveryEvent;
use NetgluePostmark\EventManager\InboundMessageEvent;
use NetgluePostmark\EventManager\OpenEvent;
use NetgluePostmark\Listener\LoggingListener;
use NetgluePostmark\Service\EventEmitter;
use NetgluePostmarkTest\TestCase;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggingListenerTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var TestHandler */
    private $testHandler;

    /** @var LoggingListener */
    private $listener;

    public function setUp()
    {
        parent::setUp();

        $this->logger = new Logger('Test');
        $this->testHandler = new TestHandler();
        $this->testHandler->clear();
        $this->logger->pushHandler($this->testHandler);
        $this->listener = new LoggingListener($this->logger);
    }

    public function testOnClick()
    {
        /** @var ClickEvent $event */
        $event = ClickEvent::factory($this->getJsonFixture('click.json'));
        $this->listener->onClick($event);
        $this->assertTrue($this->testHandler->hasInfoThatContains('just received a click'));
    }

    public function testOpen()
    {
        /** @var OpenEvent $event */
        $event = OpenEvent::factory($this->getJsonFixture('open.json'));
        $this->listener->onOpen($event);
        $this->assertTrue($this->testHandler->hasInfoThatContains('was just opened'));
    }

    public function testDelivery()
    {
        /** @var DeliveryEvent $event */
        $event = DeliveryEvent::factory($this->getJsonFixture('delivery.json'));
        $this->listener->onDelivery($event);
        $this->assertTrue($this->testHandler->hasInfoThatContains('was just successfully delivered'));
    }

    public function testInbound()
    {
        /** @var InboundMessageEvent $event */
        $event = InboundMessageEvent::factory($this->getJsonFixture('inbound.json'));
        $this->listener->onEmailReceived($event);
        $this->assertTrue($this->testHandler->hasInfoThatContains('was just received'));
    }

    public function bounceTypes() : array
    {
        return [
            ['bounce.json'],
            ['soft-bounce.json'],
            ['spam-complaint.json'],
        ];
    }

    /**
     * @dataProvider bounceTypes
     */
    public function testBounce($jsonFile)
    {
        /** @var BounceEvent $event */
        $event = BounceEvent::factory($this->getJsonFixture($jsonFile));
        $this->listener->onBounce($event);
        $this->assertTrue($this->testHandler->hasWarningThatContains('just bounced with code'));
    }

    public function getOutboundFixtures() : array
    {
        return [
            ['bounce.json', null, 'just bounced with code'],
            ['click.json', 'just received a click', null],
            ['delivery.json', 'was just successfully delivered', null],
            ['open.json', 'was just opened', null],
            ['soft-bounce.json', null, 'just bounced with code'],
            ['spam-complaint.json', null, 'just bounced with code'],
        ];
    }

    /**
     * @dataProvider getOutboundFixtures
     */
    public function testAttachmentToEventEmitter(string $jsonFile, ?string $expectInfo, ?string $expectWarning)
    {
        $emitter = new EventEmitter();
        $this->listener->attach($emitter->getEventManager());
        $emitter->process($this->getJsonFixture($jsonFile));
        if ($expectInfo) {
            $this->assertTrue($this->testHandler->hasInfoThatContains($expectInfo));
        }
        if ($expectWarning) {
            $this->assertTrue($this->testHandler->hasWarningThatContains($expectWarning));
        }
    }

    /**
     * @expectedExceptionMessage A Psr logger cannot be found in the container
     * @expectedException \NetgluePostmark\Exception\ConfigException
     */
    public function testFactoryExceptionWithNoLogger()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(LoggerInterface::class)->willReturn(false);
        $factory = new LoggingListenerDelegatorFactory();
        ($factory)($container->reveal(), 'Foo', function () {
            return new EventEmitter();
        });
    }

    /**
     * @expectedExceptionMessage Expected callback to return an
     * @expectedException \NetgluePostmark\Exception\RuntimeException
     */
    public function testFactoryExceptionWithInvalidCallback()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(LoggerInterface::class)->willReturn(true);
        $container->get(LoggerInterface::class)->willReturn($this->logger);
        $factory = new LoggingListenerDelegatorFactory();
        ($factory)($container->reveal(), 'Foo', function () {
            return new \stdClass();
        });
    }

    public function testFactory()
    {
        $emitter = new EventEmitter();
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(LoggerInterface::class)->willReturn(true);
        $container->get(LoggerInterface::class)->willReturn($this->logger);
        $factory = new LoggingListenerDelegatorFactory();
        $result = ($factory)($container->reveal(), 'Foo', function () use ($emitter) {
            return $emitter;
        });
        $this->assertSame($emitter, $result);
    }
}
