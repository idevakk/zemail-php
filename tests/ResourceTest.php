<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Zemail\Client;
use Zemail\Models\Mailbox;

it('fetches a list of mailboxes', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'object' => 'list',
            'data' => [
                [
                    'id' => 123,
                    'address' => 'test@zemail.me',
                    'type' => 'random',
                    'domain' => 'zemail.me',
                    'expires_at' => null,
                    'created_at' => '2026-08-16T12:00:00Z',
                    'unread_count' => 0,
                    'emails_count' => 0,
                ],
            ],
            'has_more' => false,
            'next_cursor' => null,
            'meta' => [
                'current_page' => 1,
                'per_page' => 25,
                'total' => 1,
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client('zm_live_test', '2026-04-23', ['handler' => $handlerStack]);

    $paginatedList = $client->mailboxes()->list();

    expect($paginatedList->data)->toBeArray()->toHaveCount(1)
        ->and($paginatedList->data[0])->toBeInstanceOf(Mailbox::class)
        ->and($paginatedList->data[0]->address)->toBe('test@zemail.me')
        ->and($paginatedList->hasMore)->toBeFalse();
});
