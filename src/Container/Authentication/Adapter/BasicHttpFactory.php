<?php
declare(strict_types=1);

namespace NetgluePostmark\Container\Authentication\Adapter;

use NetgluePostmark\Authentication\Adapter\BasicInMemoryResolver;
use NetgluePostmark\Exception;
use Psr\Container\ContainerInterface;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;

class BasicHttpFactory
{
    public function __invoke(ContainerInterface $container) : BasicHttpAuth
    {
        $config = $container->get('config')['postmark']['webhookOptions'];
        if (empty($config['username']) || empty($config['password'])) {
            throw new Exception\ConfigException(
                'To use HTTP basic auth, you must configure a non-empty username and password'
            );
        }
        $resolver = new BasicInMemoryResolver($config['username'], $config['password']);
        $realm = isset($config['realm']) ? $config['realm'] : 'Postmark';
        $options = [
            'realm' => $realm,
            'accept_schemes' => 'basic',
        ];
        $adapter = new BasicHttpAuth($options);
        $adapter->setBasicResolver($resolver);

        return $adapter;
    }
}
