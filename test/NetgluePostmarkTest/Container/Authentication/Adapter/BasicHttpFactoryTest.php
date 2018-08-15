<?php
declare(strict_types=1);

namespace NetgluePostmarkTest\Container\Authentication\Adapter;

use NetgluePostmark\Container\Authentication\Adapter\BasicHttpFactory;
use NetgluePostmarkTest\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;
use Zend\Http\Request;
use Zend\Http\Response;

class BasicHttpFactoryTest extends TestCase
{

    /** @var BasicHttpFactory */
    private $factory;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new BasicHttpFactory();
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * @expectedException \NetgluePostmark\Exception\ConfigException
     * @expectedExceptionMessage To use HTTP basic auth, you must configure a non-empty username and password
     */
    public function testMissingUsernameOrPasswordIsExceptional()
    {
        $this->container->get('config')->willReturn([
            'postmark' => [
                'webhookOptions' => [
                    'basicAuth' => true,
                    'username' => null,
                    'password' => null,
                ],
            ],
        ]);
        ($this->factory)($this->container->reveal());
    }

    public function testFactory()
    {
        $this->container->get('config')->willReturn([
            'postmark' => [
                'webhookOptions' => [
                    'username' => 'foo',
                    'password' => 'bar',
                ],
            ],
        ]);
        /** @var BasicHttpAuth $adapter */
        $adapter = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(BasicHttpAuth::class, $adapter);
        $headers = $this->request->getHeaders();
        $headers->addHeaderLine(sprintf('Authorization: Basic %s', \base64_encode('username:password')));
        $adapter->setRequest($this->request);
        $adapter->setResponse($this->response);
        $result = $adapter->authenticate();
        $this->assertFalse($result->isValid());

        $headers->clearHeaders();
        $headers->addHeaderLine(sprintf('Authorization: Basic %s', \base64_encode('foo:bar')));
        $result = $adapter->authenticate();
        $this->assertTrue($result->isValid());
    }
}
