<?php

namespace Zemail;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Zemail\Exceptions\AuthenticationException;
use Zemail\Exceptions\NotFoundException;
use Zemail\Exceptions\RateLimitException;
use Zemail\Exceptions\ValidationException;
use Zemail\Exceptions\ZemailException;
use Zemail\Resources\AccountResource;
use Zemail\Resources\DomainResource;
use Zemail\Resources\MailboxResource;

class Client
{
    private GuzzleClient $httpClient;

    private string $apiKey;

    private ?string $version;

    private ?AccountResource $account = null;

    private ?DomainResource $domains = null;

    private ?MailboxResource $mailboxes = null;

    public function __construct(string $apiKey, ?string $version = '2026-04-23', array $guzzleOptions = [])
    {
        $this->apiKey = $apiKey;
        $this->version = $version;

        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'zemail-php-sdk/1.0.0',
        ];

        if ($this->version) {
            $headers['Zemail-Version'] = $this->version;
        }

        $customHeaders = $guzzleOptions['headers'] ?? [];
        unset($guzzleOptions['headers']);

        $options = array_merge([
            'base_uri' => 'https://zemail.me',
            'headers' => array_merge($headers, $customHeaders),
        ], $guzzleOptions);

        $this->httpClient = new GuzzleClient($options);
    }

    public function getHttpClient(): GuzzleClient
    {
        return $this->httpClient;
    }

    public function account(): AccountResource
    {
        if ($this->account === null) {
            $this->account = new AccountResource($this);
        }

        return $this->account;
    }

    public function domains(): DomainResource
    {
        if ($this->domains === null) {
            $this->domains = new DomainResource($this);
        }

        return $this->domains;
    }

    public function mailboxes(): MailboxResource
    {
        if ($this->mailboxes === null) {
            $this->mailboxes = new MailboxResource($this);
        }

        return $this->mailboxes;
    }

    /**
     * @return array|mixed
     *
     * @throws ZemailException
     */
    public function request(string $method, string $uri, array $options = [])
    {
        try {
            $response = $this->httpClient->request($method, $uri, $options);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $this->handleException($e);
        } catch (GuzzleException $e) {
            throw new ZemailException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws ZemailException
     */
    private function handleException(ClientException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $rawBody = $response->getBody()->getContents();
        $body = json_decode($rawBody, true);

        $error = is_array($body) ? ($body['error'] ?? null) : null;
        if (! $error) {
            $snippet = substr($rawBody, 0, 500);
            throw new ZemailException("An unknown error occurred. Status Code: {$statusCode}. Response Snippet: {$snippet}", $statusCode, $e);
        }

        $message = $error['message'] ?? 'Unknown error';
        $code = $error['code'] ?? null;
        $param = $error['param'] ?? null;
        $requestId = $error['request_id'] ?? null;
        $errors = $error['errors'] ?? [];

        switch ($statusCode) {
            case 401:
            case 403:
                throw new AuthenticationException($message, $statusCode, $e, $code, $param, $requestId);
            case 404:
                throw new NotFoundException($message, $statusCode, $e, $code, $param, $requestId);
            case 422:
                if ($code === 'validation_failed') {
                    throw new ValidationException($message, $statusCode, $e, $code, $param, $requestId, $errors);
                }
                throw new ZemailException($message, $statusCode, $e, $code, $param, $requestId);
            case 429:
                throw new RateLimitException($message, $statusCode, $e, $code, $param, $requestId);
            default:
                throw new ZemailException($message, $statusCode, $e, $code, $param, $requestId);
        }
    }
}
