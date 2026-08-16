<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Zemail\Client;
use Zemail\Exceptions\AuthenticationException;
use Zemail\Exceptions\ValidationException;

it('sets the correct default headers', function () {
    $client = new Client('zm_live_test123', '2026-04-23');
    $guzzle = $client->getHttpClient();
    $config = $guzzle->getConfig();

    expect($config['headers']['Authorization'])->toBe('Bearer zm_live_test123')
        ->and($config['headers']['Accept'])->toBe('application/json')
        ->and($config['headers']['Zemail-Version'])->toBe('2026-04-23')
        ->and($config['headers']['User-Agent'])->toBe('zemail-php-sdk/1.0.0');
});

it('merges custom headers', function () {
    $client = new Client('zm_live_test123', '2026-04-23', [
        'headers' => [
            'User-Agent' => 'custom-agent',
            'X-Custom-Header' => 'value',
        ],
    ]);
    $guzzle = $client->getHttpClient();
    $config = $guzzle->getConfig();

    expect($config['headers']['Authorization'])->toBe('Bearer zm_live_test123')
        ->and($config['headers']['Accept'])->toBe('application/json')
        ->and($config['headers']['Zemail-Version'])->toBe('2026-04-23')
        ->and($config['headers']['User-Agent'])->toBe('custom-agent')
        ->and($config['headers']['X-Custom-Header'])->toBe('value');
});

it('throws AuthenticationException on 401', function () {
    $mock = new MockHandler([
        new Response(401, [], json_encode([
            'error' => [
                'type' => 'authentication_error',
                'code' => 'invalid_api_key',
                'message' => 'The provided API key is invalid.',
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client('bad_key', '2026-04-23', ['handler' => $handlerStack]);

    $client->account()->get();
})->throws(AuthenticationException::class, 'The provided API key is invalid.');

it('throws ValidationException on 422 validation_failed', function () {
    $mock = new MockHandler([
        new Response(422, [], json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'validation_failed',
                'message' => 'The request payload failed validation.',
                'errors' => [
                    'username' => ['The username field is required.'],
                ],
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client('zm_live_test', '2026-04-23', ['handler' => $handlerStack]);

    try {
        $client->mailboxes()->create(['type' => 'custom']);
    } catch (ValidationException $e) {
        expect($e->errors)->toHaveKey('username')
            ->and($e->errors['username'][0])->toBe('The username field is required.');
        throw $e;
    }
})->throws(ValidationException::class);
