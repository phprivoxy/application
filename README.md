# phprivoxy/application
## Framework for proxy-server applications creation with PSR15 middleware as traffic handlers.

This proxy-server application framework based on Workerman framework (https://github.com/walkor/workerman).

With this framework proxy-servers creation come to appropriate PSR15 middlewares developing with necessary functionality.

### Requirements 
- **PHP >= 8.1**

### Installation
#### Using composer (recommended)
```bash
composer create phprivoxy/application
```

### Simple SSL MITM (Man In The Middle) proxy sample

```php
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

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
        $client = new GuzzleHttp\Client();
        try {
            $response = $client->send($request, ['allow_redirects' => false]);
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            // Do something
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            return $e->getResponse();
        }

        return $response;
    }
}

$processes = 4; // Default 1.

$app = new PHPrivoxy\Application\Application($processes);
$app->add(new DummyMiddleware());
$app->add(new HttpClientMiddleware()); // HttpClient must be last in queue (it generate response).
$app->run(); // By default, it listen all connections on 8080 port.
```
This sample you also may find at "tests" directory.

Just run it:
```bash
php tests/test.php start
```
On first run this Application create a self-signed SSL root certificate in CA subdirectory. Add this self-signed CA certificate in your browser trusted certificates!

For each site Application will generate self-signed certificate in "certificates" subdirectory.

In this sample, we use simple PSR-15 compatible HttpClientMiddleware for site downloading. You also may add your own PSR-15 compatible Middlewares in queue according to your goals and needs.

### License
MIT License See [LICENSE](LICENSE)
