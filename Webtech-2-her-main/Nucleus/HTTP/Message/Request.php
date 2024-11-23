<?php

namespace Psr\Http\Message;

class Request implements RequestInterface
{

    private string $protocolVersion = '1.1';
    private array $headers;
    private $body;
    private string $method;
    private Uri $uri;
    private string $requestTarget;
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function __construct(string $method, Uri $uri, $body, $headers)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->body = $body;
        $this->headers = $headers;
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

    public function withoutHeader(string $name): Request|static
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

    public function withBody($body): Request|static
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function getRequestTarget(): string
    {
        if($this->requestTarget !== null){
            return $this->requestTarget;
        }
        return "/";
    }

    public function withRequestTarget(string $requestTarget): Request|static
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): Request|static
    {
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): void
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $uri->getHost() !== null) {
            $new->withHeader('Host', $uri->getHost());
        }
    }
}