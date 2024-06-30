<?php

declare(strict_types=1);

namespace PHPrivoxy\Application;

use PHPrivoxy\Core\RootPath;
use PHPrivoxy\Core\Server;
use PHPrivoxy\Core\ServerWorker;
use PHPrivoxy\Proxy\MITM;
use PHPrivoxy\Proxy\MITM\ContextProvider;
use PHPrivoxy\Proxy\PSR15Proxy;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;

class Application
{
    use RootPath;

// Multiple processes cannot be set using the count parameter in a Windows system.
//A single workerman process in a Windows system can only support 200+ connections.

    private int $defaultProcesses = 1;
    private int $defaultPort = 8080;
    private string $defaultIP = '0.0.0.0';
    private static string $logSubdirName = 'var/log';
    private static string $tmpSubdirName = 'var/tmp';
    private ?int $processes; // Number of workers processes.
    private ?int $port; // PHPrivoxy port.
    private ?string $ip; // PHPrivoxy IP.
    private ?PSR15Proxy $tcpHandler = null;
    private ?RequestHandlerInterface $psr15handler = null;
    private ?ContextProvider $contextProvider = null;
    private ?string $mitmHost = null; // MITM worker host.
    private array $middlewares = [];
    private ?string $logDirectory;
    private ?string $tmpDirectory;

    public function __construct(
            ?int $processes = null,
            ?int $port = null,
            ?string $ip = null,
            ?PSR15Proxy $tcpHandler = null,
            ?RequestHandlerInterface $psr15handler = null,
            ?ContextProvider $contextProvider = null,
            ?string $mitmHost = null
    )
    {
        $this->processes = $processes;
        $this->port = $port;
        $this->ip = $ip;
        $this->tcpHandler = $tcpHandler;
        $this->psr15handler = $psr15handler;
        $this->contextProvider = $contextProvider;
        $this->mitmHost = $mitmHost;
    }

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function addAsFirst(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    public function run(): void
    {
        if (empty($this->processes)) {
            $this->processes = $this->defaultProcesses;
        }

        if (empty($this->port)) {
            $this->port = $this->defaultPort;
        }

        if (empty($this->ip)) {
            $this->ip = $this->defaultIP;
        }

        if (empty($this->logDirectory)) {
            $this->logDirectory = self::getLogDirectory();
        }

        if (empty($this->tmpDirectory)) {
            $this->tmpDirectory = self::getTmpDirectory();
        }

        if (null === $this->psr15handler) {
            $this->psr15handler = new Relay($this->middlewares); // Relay will execute the queue in first-in-first-out order.
        }

        if (null === $this->tcpHandler) {
            $this->tcpHandler = new MITM($this->psr15handler, $this->contextProvider, $this->logDirectory, $this->mitmHost);
        }

        ServerWorker::setLogDirectory($this->logDirectory);
        ServerWorker::setTmpDirectory($this->tmpDirectory);
        new Server($this->tcpHandler, $this->processes, $this->port, $this->ip);
    }

    public static function getLogDirectory(): string
    {
        return self::getRootPath() . '/' . self::$logSubdirName;
    }

    public static function getTmpDirectory(): string
    {
        return self::getRootPath() . '/' . self::$tmpSubdirName;
    }

    public function setLogDirectory(?string $path): void
    {
        $this->logDirectory = $path;
    }

    public function setTmpDirectory(?string $path): void
    {
        $this->tmpDirectory = $path;
    }

    public function setTcpHandler(PSR15Proxy $tcpHandler): void
    {
        $this->tcpHandler = $tcpHandler;
    }

    public function setPSR15Handler(RequestHandlerInterface $psr15handler): void
    {
        $this->psr15handler = $psr15handler;
    }

    public function setContextProvider(ContextProvider $contextProvider): void
    {
        $this->contextProvider = $contextProvider;
    }

    public function setMitmHost(string $mitmHost): void
    {
        $this->mitmHost = $mitmHost;
    }

    public function setProcesses(int $processes): void
    {
        if (0 >= $processes) {
            return;
        }
        $this->processes = $processes;
    }

    public function setPort(int $port): void
    {
        if (0 >= $port) {
            return;
        }
        $this->port = $port;
    }

    public function setIP(string $ip): void
    {
        if (empty($ip)) {
            return;
        }
        $this->ip = $ip;
    }

    public function getTcpHandler(): ?PSR15Proxy
    {
        return $this->tcpHandler;
    }

    public function getPSR15Handler(): ?RequestHandlerInterface
    {
        return $this->psr15handler;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
