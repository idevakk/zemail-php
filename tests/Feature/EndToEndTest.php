<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Zemail\Client;
use Zemail\Exceptions\AuthenticationException;
use Zemail\Exceptions\NotFoundException;
use Zemail\Exceptions\RateLimitException;
use Zemail\Exceptions\ValidationException;
use Zemail\Models\Account;
use Zemail\Models\Domain;
use Zemail\Models\EmailDetail;
use Zemail\Models\EmailSummary;
use Zemail\Models\Mailbox;
use Zemail\Models\PaginatedList;
use Zemail\Models\Subscription;
use Zemail\Models\Usage;

function createMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);

    return new Client('zm_live_mock_key', '2026-04-23', ['handler' => $handlerStack]);
}

it('fetches account profile, subscription, and usage', function () {
    $client = createMockClient([
        // 1. Account profile
        new Response(200, [], json_encode([
            'data' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'email_verified_at' => '2026-01-01T00:00:00Z',
                'tier' => 'pro',
                'tier_label' => 'Pro Plan',
                'current_plan' => ['name' => 'Pro'],
                'developer_api' => ['enabled' => true],
            ],
        ])),
        // 2. Subscription
        new Response(200, [], json_encode([
            'data' => [
                'status' => 'active',
                'tier' => 'pro',
                'plan' => ['name' => 'Pro Monthly', 'price' => 2900],
                'starts_at' => '2026-01-01T00:00:00Z',
                'ends_at' => '2026-12-31T23:59:59Z',
                'cancelled_at' => null,
            ],
        ])),
        // 3. Usage
        new Response(200, [], json_encode([
            'data' => [
                'mailboxes' => ['used' => 5, 'limit' => 50],
                'storage' => ['used_bytes' => 1024000, 'limit_bytes' => 104857600],
                'developer_api' => ['requests_today' => 120, 'limit_daily' => 10000],
            ],
        ])),
    ]);

    // 1. Account profile
    $account = $client->account()->get();
    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->id)->toBe(1)
        ->and($account->name)->toBe('John Doe')
        ->and($account->email)->toBe('john@example.com')
        ->and($account->tier)->toBe('pro');

    // 2. Subscription
    $subscription = $client->account()->subscription();
    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->status)->toBe('active')
        ->and($subscription->tier)->toBe('pro')
        ->and($subscription->plan)->toBeArray();

    // 3. Usage
    $usage = $client->account()->usage();
    expect($usage)->toBeInstanceOf(Usage::class)
        ->and($usage->mailboxes)->toBeArray()
        ->and($usage->storage)->toBeArray()
        ->and($usage->developerApi)->toBeArray();
});

it('lists available domains', function () {
    $client = createMockClient([
        new Response(200, [], json_encode([
            'data' => [
                [
                    'id' => 10,
                    'name' => 'zemail.me',
                    'allowed_types' => ['random', 'custom'],
                ],
                [
                    'id' => 11,
                    'name' => 'mail.zemail.me',
                    'allowed_types' => ['public'],
                ],
            ],
            'has_more' => false,
            'next_cursor' => null,
            'meta' => ['total' => 2],
        ])),
    ]);

    $domainsList = $client->domains()->list();
    expect($domainsList)->toBeInstanceOf(PaginatedList::class)
        ->and($domainsList->data)->toHaveCount(2)
        ->and($domainsList->data[0])->toBeInstanceOf(Domain::class)
        ->and($domainsList->data[0]->id)->toBe(10)
        ->and($domainsList->data[0]->name)->toBe('zemail.me')
        ->and($domainsList->data[0]->allowedTypes)->toBe(['random', 'custom']);
});

it('lists mailboxes with pagination and limit', function () {
    $client = createMockClient([
        new Response(200, [], json_encode([
            'data' => [
                [
                    'id' => 101,
                    'address' => 'box1@zemail.me',
                    'type' => 'random',
                    'domain' => 'zemail.me',
                    'expires_at' => null,
                    'created_at' => '2026-08-01T00:00:00Z',
                    'unread_count' => 2,
                    'emails_count' => 5,
                ],
            ],
            'has_more' => true,
            'next_cursor' => 'cursor_123',
            'meta' => [
                'current_page' => 1,
                'per_page' => 1,
                'total' => 20,
            ],
        ])),
    ]);

    $mailboxesList = $client->mailboxes()->list(1, 1);
    expect($mailboxesList)->toBeInstanceOf(PaginatedList::class)
        ->and($mailboxesList->data)->toHaveCount(1)
        ->and($mailboxesList->hasMore)->toBeTrue()
        ->and($mailboxesList->nextCursor)->toBe('cursor_123')
        ->and($mailboxesList->meta['total'])->toBe(20);
});

it('creates, fetches, and deletes a random mailbox with emails workflow', function () {
    $client = createMockClient([
        // 1. Create mailbox
        new Response(200, [], json_encode([
            'data' => [
                'id' => 501,
                'address' => 'random123@zemail.me',
                'type' => 'random',
                'domain' => 'zemail.me',
                'expires_at' => null,
                'created_at' => '2026-08-16T12:00:00Z',
                'unread_count' => 0,
                'emails_count' => 0,
            ],
        ])),
        // 2. Fetch mailbox
        new Response(200, [], json_encode([
            'data' => [
                'id' => 501,
                'address' => 'random123@zemail.me',
                'type' => 'random',
                'domain' => 'zemail.me',
                'expires_at' => null,
                'created_at' => '2026-08-16T12:00:00Z',
                'unread_count' => 1,
                'emails_count' => 1,
            ],
        ])),
        // 3. List emails
        new Response(200, [], json_encode([
            'data' => [
                [
                    'id' => 901,
                    'sender' => 'Sender <sender@example.com>',
                    'sender_email' => 'sender@example.com',
                    'subject' => 'Welcome Verification',
                    'preview' => 'Your verification code is 123456',
                    'received_at' => '2026-08-16T12:05:00Z',
                    'is_read' => false,
                    'is_blocked' => false,
                    'attachments_count' => 1,
                ],
            ],
            'has_more' => false,
            'next_cursor' => null,
            'meta' => ['total' => 1],
        ])),
        // 4. Get email detail
        new Response(200, [], json_encode([
            'data' => [
                'id' => 901,
                'sender' => 'Sender <sender@example.com>',
                'sender_name' => 'Sender',
                'sender_email' => 'sender@example.com',
                'subject' => 'Welcome Verification',
                'preview' => 'Your verification code is 123456',
                'received_at' => '2026-08-16T12:05:00Z',
                'is_read' => false,
                'is_blocked' => false,
                'blocked_domain' => null,
                'body_text' => 'Your verification code is 123456',
                'body_html' => '<p>Your verification code is <strong>123456</strong></p>',
                'attachments' => [
                    [
                        'id' => 'att_abc123',
                        'name' => 'welcome.pdf',
                        'size' => 2048,
                        'downloadable' => true,
                    ],
                ],
            ],
        ])),
        // 5. Mark as read
        new Response(200, [], json_encode([
            'is_read' => true,
        ])),
        // 6. Get attachment download URL
        new Response(200, [], json_encode([
            'url' => 'https://download.zemail.me/att_abc123?token=xyz',
            'expires_at' => '2026-08-16T13:00:00Z',
        ])),
        // 7. Delete email
        new Response(200, [], json_encode([
            'deleted' => true,
        ])),
        // 8. Delete mailbox
        new Response(200, [], json_encode([
            'data' => ['deleted' => true],
        ])),
    ]);

    // Create random mailbox
    $mailbox = $client->mailboxes()->create(['type' => 'random']);
    expect($mailbox)->toBeInstanceOf(Mailbox::class)
        ->and($mailbox->id)->toBe(501)
        ->and($mailbox->address)->toBe('random123@zemail.me')
        ->and($mailbox->domain)->toBe('zemail.me');

    // Fetch created mailbox
    $fetched = $client->mailboxes()->get($mailbox->id);
    expect($fetched->id)->toBe(501)
        ->and($fetched->unreadCount)->toBe(1);

    // List emails
    $emails = $client->mailboxes()->emails()->list($mailbox->id);
    expect($emails)->toBeInstanceOf(PaginatedList::class)
        ->and($emails->data)->toHaveCount(1)
        ->and($emails->data[0])->toBeInstanceOf(EmailSummary::class)
        ->and($emails->data[0]->id)->toBe(901);

    // Get email detail
    $emailDetail = $client->mailboxes()->emails()->get($mailbox->id, 901);
    expect($emailDetail)->toBeInstanceOf(EmailDetail::class)
        ->and($emailDetail->id)->toBe(901)
        ->and($emailDetail->bodyText)->toContain('123456')
        ->and($emailDetail->attachments)->toHaveCount(1)
        ->and($emailDetail->attachments[0]->id)->toBe('att_abc123');

    // Mark as read
    $markRead = $client->mailboxes()->emails()->markAsRead($mailbox->id, 901);
    expect($markRead)->toBeTrue();

    // Get attachment download URL
    $attachmentUrl = $client->mailboxes()->emails()->getAttachmentDownloadUrl($mailbox->id, 901, 'att_abc123');
    expect($attachmentUrl)->toBeArray()
        ->and($attachmentUrl['url'])->toContain('download.zemail.me')
        ->and($attachmentUrl['expires_at'])->toBe('2026-08-16T13:00:00Z');

    // Delete email
    $emailDeleted = $client->mailboxes()->emails()->delete($mailbox->id, 901);
    expect($emailDeleted)->toBeTrue();

    // Delete mailbox
    $mailboxDeleted = $client->mailboxes()->delete($mailbox->id);
    expect($mailboxDeleted)->toBeTrue();
});

it('creates and deletes a custom mailbox', function () {
    $client = createMockClient([
        // Create custom mailbox
        new Response(200, [], json_encode([
            'data' => [
                'id' => 777,
                'address' => 'custombox@mydomain.com',
                'type' => 'custom',
                'domain' => 'mydomain.com',
                'expires_at' => null,
                'created_at' => '2026-08-16T12:00:00Z',
                'unread_count' => 0,
                'emails_count' => 0,
            ],
        ])),
        // Delete mailbox
        new Response(200, [], json_encode([
            'deleted' => true,
        ])),
    ]);

    $mailbox = $client->mailboxes()->create([
        'type' => 'custom',
        'domain' => 'mydomain.com',
        'username' => 'custombox',
    ]);

    expect($mailbox)->toBeInstanceOf(Mailbox::class)
        ->and($mailbox->id)->toBe(777)
        ->and($mailbox->address)->toBe('custombox@mydomain.com')
        ->and($mailbox->domain)->toBe('mydomain.com');

    $deleted = $client->mailboxes()->delete($mailbox->id);
    expect($deleted)->toBeTrue();
});

it('handles API errors correctly', function () {
    // 1. 401 Unauthorized
    $unauthClient = createMockClient([
        new Response(401, [], json_encode([
            'error' => [
                'type' => 'authentication_error',
                'code' => 'invalid_api_key',
                'message' => 'The provided API key is invalid.',
            ],
        ])),
    ]);
    expect(fn () => $unauthClient->account()->get())
        ->toThrow(AuthenticationException::class, 'The provided API key is invalid.');

    // 2. 404 Not Found
    $notFoundClient = createMockClient([
        new Response(404, [], json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'resource_missing',
                'message' => 'Mailbox not found.',
            ],
        ])),
    ]);
    expect(fn () => $notFoundClient->mailboxes()->get(99999))
        ->toThrow(NotFoundException::class, 'Mailbox not found.');

    // 3. 422 Validation Error
    $valClient = createMockClient([
        new Response(422, [], json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'validation_failed',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'domain' => ['The selected domain is invalid.'],
                ],
            ],
        ])),
    ]);
    expect(fn () => $valClient->mailboxes()->create(['type' => 'custom']))
        ->toThrow(ValidationException::class, 'The given data was invalid.');

    // 4. 429 Rate Limit
    $rateLimitClient = createMockClient([
        new Response(429, [], json_encode([
            'error' => [
                'type' => 'rate_limit_error',
                'code' => 'rate_limit_exceeded',
                'message' => 'Too many requests.',
            ],
        ])),
    ]);
    expect(fn () => $rateLimitClient->account()->usage())
        ->toThrow(RateLimitException::class, 'Too many requests.');
});
