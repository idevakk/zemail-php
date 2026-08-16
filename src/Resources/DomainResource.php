<?php

namespace Zemail\Resources;

use Zemail\Client;
use Zemail\Exceptions\ZemailException;
use Zemail\Models\Domain;
use Zemail\Models\PaginatedList;

class DomainResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List domains available for mailbox creation
     *
     * @throws ZemailException
     */
    public function list(): PaginatedList
    {
        $response = $this->client->request('GET', '/api/domains');

        $domains = array_map(function ($item) {
            return Domain::fromArray($item);
        }, $response['data'] ?? []);

        return new PaginatedList(
            $domains,
            $response['has_more'] ?? false,
            $response['next_cursor'] ?? null,
            $response['meta'] ?? []
        );
    }
}
