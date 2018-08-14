<?php
declare(strict_types=1);

namespace NetgluePostmark\Controller;

use NetgluePostmark\Service\EventEmitter;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use function sprintf;

class WebhookController extends AbstractActionController
{
    /** @var BasicHttpAuth|null */
    private $auth = null;

    /**  @var EventEmitter */
    private $emitter;

    public function __construct(EventEmitter $service)
    {
        $this->emitter = $service;
    }

    /**
     * Receive Postmark Webhooks
     *
     * @return JsonModel
     */
    public function webhookAction() : JsonModel
    {
        $request  = $this->getRequest();
        $response = $this->getResponse();
        if ((! $request instanceof Request) || (! $response instanceof Response)) {
            return $this->appError(sprintf(
                'Invalid request or response object. Expected instances of %s and %s',
                Request::class,
                Response::class
            ), 400);
        }
        /**
         * If Basic Auth is configured, authenticate the request
         */
        if ($this->auth) {
            $this->auth->setRequest($request);
            $this->auth->setResponse($response);
            $result = $this->auth->authenticate();
            if (! $result->isValid()) {
                return $this->appError('Authentication Failed', $response->getStatusCode(), 'auth_error');
            }
        }

        /**
         * All hooks are POSTed
         */
        if (! $request->isPost()) {
            return $this->appError('Method Not Allowed', 405, 'general_error');
        }

        /**
         * Trigger Events for Listeners
         */
        $this->emitter->process($request->getContent());

        /**
         * Return an Empty 200 Response
         */
        return new JsonModel();
    }

    /**
     * Set (Ready Configured) Basic Auth Adapter
     * @param BasicHttpAuth $adapter
     * @return void
     */
    public function setBasicAuth(BasicHttpAuth $adapter) : void
    {
        $this->auth = $adapter;
    }

    /**
     * Raise a generic app error
     *
     * @param string $message
     * @param int    $code
     * @param string $type
     * @return JsonModel
     */
    private function appError(string $message, int $code = 400, string $type = 'general_error') : JsonModel
    {
        $e = $this->getEvent();
        $response = $e->getResponse();
        if ($response instanceof Response) {
            $response->setStatusCode($code);
        }
        return new JsonModel([
            'error' => [
                'type' => $type,
                'message' => $message,
                'code' => $code
            ]
        ]);
    }
}
