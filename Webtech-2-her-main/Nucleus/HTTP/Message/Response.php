<?php

namespace Psr\Http\Message;
class Response implements ResponseInterface
{
    private string $protocolVersion = '1.1';
    private array $headers;
    private $body;
    private int $statusCode;
    private string $reasonPhrase;

    public function __construct($statusCode, $headers, $reasonPhrase, $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->reasonPhrase = $reasonPhrase;
        $this->body = $body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): Response|static
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name)
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->headers[strtolower($name)] ?? []);
    }

    public function withHeader(string $name, $value): Response|static
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = $value ?? [$value];
        return $new;
    }

    public function withAddedHeader(string $name, $value): Response|static
    {
        $new = clone $this;
        if(isset($this->headers[strtolower($name)])){
            return $new;
        }
        array_push($new->headers, $value);
        return $new;
    }

    public function withoutHeader(string $name): Response|static
    {
        $new = clone $this;
        if(isset($new->headers[strtolower($name)])){
            unset($new->headers[strtolower($name)]);
        }
        return $new;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody($body): Response|static
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): Response|static
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}