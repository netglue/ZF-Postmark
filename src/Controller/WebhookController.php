<?php
declare(strict_types=1);

namespace NetgluePostmark\Controller;

use NetgluePostmark\Service\EventEmitter;
use NetgluePostmark\Exception;
use Psr\Log\LoggerInterface;
use Throwable;
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

    /** @var null|LoggerInterface */
    private $logger;

    /** @var bool */
    private $throwExceptions;

    public function __construct(EventEmitter $service, bool $throwExceptions = false, ?LoggerInterface $logger = null)
    {
        $this->emitter = $service;
        $this->logger  = $logger;
        $this->throwExceptions = $throwExceptions;
    }

    /**
     * Receive Postmark Webhooks
     *
     * @return JsonModel
     */
    public function webhookAction() : JsonModel
    {
        $request = $this->validateRequest();
        if ($request instanceof JsonModel) {
            return $request;
        }

        /** Trigger Event */
        try {
            $this->emitter->process($request->getContent());
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }

        /** Return an Empty 200 Response */
        return new JsonModel();
    }

    public function inboundAction() : JsonModel
    {
        $request = $this->validateRequest();
        if ($request instanceof JsonModel) {
            return $request;
        }

        /** Trigger Event */
        try {
            $this->emitter->processInbound($request->getContent());
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }

        /** Return an Empty 200 Response */
        return new JsonModel();
    }


    private function handleException(Throwable $exception) : JsonModel
    {
        if ($this->throwExceptions) {
            throw new Exception\RuntimeException('An exception occurred during processing', 500, $exception);
        }
        if ($this->logger) {
            $this->logger->error('An exception occurred processing a Postmark webhook', [
                'exception' => $exception
            ]);
        }
        return $this->appError(
            'Sorry an error occurred processing this request',
            500,
            'exception'
        );
    }

    /**
     * @return Request|JsonModel
     */
    private function validateRequest()
    {
        /** HTTP ONLY */
        $request  = $this->getRequest();
        $response = $this->getResponse();
        if ((! $request instanceof Request) || (! $response instanceof Response)) {
            return $this->appError(sprintf(
                'Invalid request or response object. Expected instances of %s and %s',
                Request::class,
                Response::class
            ), 400);
        }

        /** All hooks are POSTed */
        if (! $request->isPost()) {
            return $this->appError('Method Not Allowed', 405, 'general_error');
        }

        /** If Basic Auth is configured, authenticate the request */
        if ($this->auth) {
            $this->auth->setRequest($request);
            $this->auth->setResponse($response);
            $result = $this->auth->authenticate();
            if (! $result->isValid()) {
                return $this->appError('Authentication Failed', $response->getStatusCode(), 'auth_error');
            }
        }

        return $request;
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
