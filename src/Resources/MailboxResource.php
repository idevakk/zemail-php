<?php

namespace Zemail\Resources;

use Zemail\Client;
use Zemail\Exceptions\ZemailException;
use Zemail\Models\Mailbox;
use Zemail\Models\PaginatedList;

class MailboxResource
{
    private Client $client;

    private ?EmailResource $emails = null;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List owned mailboxes
     *
     * @throws ZemailException
     */
    public function list(int $page = 1, ?int $limit = null): PaginatedList
    {
        $query = ['page' => $page];
        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        $response = $this->client->request('GET', '/api/mailboxes', [
            'query' => $query,
        ]);

        $mailboxes = array_map(function ($item) {
            return Mailbox::fromArray($item);
        }, $response['data'] ?? []);

        return new PaginatedList(
            $mailboxes,
            $response['has_more'] ?? false,
            $response['next_cursor'] ?? null,
            $response['meta'] ?? []
        );
    }

    /**
     * Create a mailbox
     *
     * @param  array  $data  e.g. ['type' => 'random'] or ['type' => 'custom', 'domain' => '...', 'username' => '...']
     *
     * @throws ZemailException
     */
    public function create(array $data): Mailbox
    {
        $response = $this->client->request('POST', '/api/mailboxes', [
            'json' => $data,
        ]);

        return Mailbox::fromArray($response['data']);
    }

    /**
     * Get a single mailbox
     *
     * @throws ZemailException
     */
    public function get(int $id): Mailbox
    {
        $response = $this->client->request('GET', "/api/mailboxes/{$id}");

        return Mailbox::fromArray($response['data']);
    }

    /**
     * Delete a mailbox
     *
     * @throws ZemailException
     */
    public function delete(int $id): bool
    {
        $response = $this->client->request('DELETE', "/api/mailboxes/{$id}");

        return ($response['data']['deleted'] ?? $response['deleted'] ?? false) === true;
    }

    /**
     * Access emails for a specific mailbox
     */
    public function emails(): EmailResource
    {
        if ($this->emails === null) {
            $this->emails = new EmailResource($this->client);
        }

        return $this->emails;
    }
}
