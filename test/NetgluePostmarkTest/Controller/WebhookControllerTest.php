<?php
declare(strict_types=1);

namespace NetglueSendgridTest\Mvc\Controller;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use NetgluePostmark\Authentication\Adapter\BasicHttp;
use NetgluePostmark\Authentication\Adapter\BasicInMemoryResolver;
use NetgluePostmark\ConfigProvider;
use NetgluePostmark\Controller\WebhookController;
use NetgluePostmark\Container\Controller\WebhookControllerFactory;
use NetgluePostmark\Service\EventEmitter;
use NetgluePostmarkTest\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Router\RouteMatch;
use Zend\View\Model\JsonModel;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;

class WebhookControllerTest extends TestCase
{

    private $emitter;

    /** @var WebhookController */
    private $controller;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    /** @var Logger */
    private $logger;

    /** @var TestHandler */
    private $testHandler;

    public function setUp()
    {
        parent::setUp();
        $this->emitter = new EventEmitter();
        $this->controller = new WebhookController($this->emitter, true);
        $this->response = new Response();
        $this->request  = new Request();
        $this->request->setContent($this->getJsonFixture('bounce.json'));
        $this->logger = new Logger('Test');
        $this->testHandler = new TestHandler();
        $this->logger->pushHandler($this->testHandler);
    }

    private function dispatchAction(string $action = 'webhook')
    {
        $event = $this->controller->getEvent();
        $event->setRouteMatch(new RouteMatch(['action' => $action]));
        return $this->controller->dispatch($this->request, $this->response);
    }

    /**
     * @dataProvider getActions
     */
    public function testControllerOnlyAcceptsPostRequests($action)
    {
        /** @var JsonModel $model */
        $model = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame('Method Not Allowed', $model->getVariable('error')['message']);
        $this->assertSame(405, $this->response->getStatusCode());
    }

    /**
     * @expectedException \NetgluePostmark\Exception\RuntimeException
     * @expectedExceptionMessage An exception occurred during processing
     * @dataProvider getActions
     */
    public function testExceptionIsThrownWhenThrowExceptionsIsTrue($action)
    {
        $this->controller = new WebhookController($this->emitter, true);
        $this->request->setMethod('POST');
        $this->request->setContent('Invalid Json');
        $this->dispatchAction($action);
    }

    /**
     * @dataProvider getActions
     */
    public function testJsonIsReturnedWhenExceptionsAreFalse($action)
    {
        $this->controller = new WebhookController($this->emitter, false);
        $this->request->setMethod('POST');
        $this->request->setContent('Invalid Json');
        $json = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $json);
        $this->assertSame('Sorry an error occurred processing this request', $json->getVariable('error')['message']);
        $this->assertFalse($this->testHandler->hasErrorRecords());
    }

    /**
     * @dataProvider getActions
     */
    public function testErrorsAreLoggedWhenExceptionsAreFalseAndLoggerIsAvailable($action)
    {
        $this->controller = new WebhookController($this->emitter, false, $this->logger);
        $this->request->setMethod('POST');
        $this->request->setContent('Invalid Json');
        $this->dispatchAction($action);
        $this->assertTrue($this->testHandler->hasErrorRecords());
    }

    private function getBasicAuth() : BasicHttpAuth
    {
        $options = [
            'realm' => 'Auth',
            'accept_schemes' => 'basic',
        ];
        $adapter = new BasicHttpAuth($options);
        $adapter->setBasicResolver(new BasicInMemoryResolver('username', 'password'));
        return $adapter;
    }

    private function prepareBasicAuth()
    {
        $this->controller->setBasicAuth($this->getBasicAuth());
    }

    /**
     * @dataProvider getActions
     */
    public function testIs401WhenAuthFails($action)
    {
        $this->prepareBasicAuth();
        $this->request->setMethod('POST');
        /** @var JsonModel $model */
        $model = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame('Authentication Failed', $model->getVariable('error')['message']);
        $this->assertSame(401, $this->response->getStatusCode());
    }

    /**
     * @dataProvider getActions
     */
    public function testIs401WithIncorrectCredentials($action)
    {
        $this->prepareBasicAuth();
        $this->request->setMethod('POST');
        $headers = $this->request->getHeaders();
        $headers->addHeaderLine(sprintf('Authorization: Basic %s', \base64_encode('wrong:wrong')));
        /** @var JsonModel $model */
        $model = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame('Authentication Failed', $model->getVariable('error')['message']);
        $this->assertSame(401, $this->response->getStatusCode());
    }

    /**
     * @dataProvider getActions
     */
    public function testIs200WhenAuthSucceeds($action)
    {
        $this->request->setMethod('POST');
        $this->prepareBasicAuth();
        $headers = $this->request->getHeaders();
        $headers->addHeaderLine(sprintf('Authorization: Basic %s', \base64_encode('username:password')));
        /** @var JsonModel $model */
        $model = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame(200, $this->response->getStatusCode());
    }

    /**
     * @dataProvider getActions
     */
    public function testPostIsSuccessWithoutAuth($action)
    {
        $this->request->setMethod('POST');
        /** @var JsonModel $model */
        $model = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame(200, $this->response->getStatusCode());
    }

    public function getActions() : array
    {
        return [
            ['webhook'],
            ['inbound'],
        ];
    }

    /**
     * @dataProvider getActions
     */
    public function testNonHttpRequestIsError($action)
    {
        $this->request = new \Zend\Stdlib\Request();
        /** @var JsonModel $model */
        $model = $this->dispatchAction($action);
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame(400, $this->response->getStatusCode());
        $this->assertContains('Invalid request or response object', $model->getVariable('error')['message']);
    }

    public function testNonHttpResponseIsError()
    {
        $this->response = new \Zend\Stdlib\Response();
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertContains('Invalid request or response object', $model->getVariable('error')['message']);
    }

    public function testFactory()
    {
        $config = (new ConfigProvider)();
        $config['postmark']['webhookOptions']['basicAuth'] = false;
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get(EventEmitter::class)->willReturn($this->emitter);
        $container->has(LoggerInterface::class)->willReturn(false);
        $controller = (new WebhookControllerFactory)($container->reveal());
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testFactorySuccessfullyConfiguresAuth()
    {
        $config = (new ConfigProvider)();
        $config['postmark']['webhookOptions']['username'] = 'me';
        $config['postmark']['webhookOptions']['password'] = 'foo';
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get(EventEmitter::class)->willReturn($this->emitter);
        $container->has(LoggerInterface::class)->willReturn(false);
        $container->get(LoggerInterface::class)->willReturn($this->logger);
        $container->get(BasicHttp::class)->willReturn($this->getBasicAuth());
        $this->controller = (new WebhookControllerFactory)($container->reveal());
        $this->dispatchAction();
        $this->assertSame(405, $this->response->getStatusCode());
    }

    /**
     * @expectedException \NetgluePostmark\Exception\ConfigException
     * @expectedExceptionMessage Expected an instance of
     */
    public function testExceptionInFactoryWhenLoggerIsNotPsr()
    {
        $config = (new ConfigProvider)();
        $config['postmark']['webhookOptions']['basicAuth'] = false;
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);
        $container->get(EventEmitter::class)->willReturn($this->emitter);
        $container->has(LoggerInterface::class)->willReturn(true);
        $container->get(LoggerInterface::class)->willReturn(new \stdClass());
        (new WebhookControllerFactory)($container->reveal());
    }

}
