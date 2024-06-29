<?php

use PHPrivoxy\Application\Application;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\BadResponseException;

require_once __DIR__ . '/../vendor/autoload.php';

class DummyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

class HttpClientMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $client = new Client();
        try {
            $response = $client->send($request, ['allow_redirects' => false]);
        } catch (ConnectException $e) {
            // Do something
        } catch (BadResponseException $e) {
            return $e->getResponse();
        }

        return $response;
    }
}

$processes = 4; // Default 1.

$app = new Application($processes);
$app->add(new DummyMiddleware());
$app->add(new HttpClientMiddleware()); // HttpClient must be last in queue (it generate response).
$app->run();
