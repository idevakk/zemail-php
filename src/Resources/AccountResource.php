<?php

namespace Zemail\Resources;

use Zemail\Client;
use Zemail\Exceptions\ZemailException;
use Zemail\Models\Account;
use Zemail\Models\Subscription;
use Zemail\Models\Usage;

class AccountResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the authenticated account snapshot
     *
     * @throws ZemailException
     */
    public function get(): Account
    {
        $response = $this->client->request('GET', '/api/account');

        return Account::fromArray($response['data']);
    }

    /**
     * Get the current subscription snapshot
     *
     * @throws ZemailException
     */
    public function subscription(): Subscription
    {
        $response = $this->client->request('GET', '/api/account/subscription');

        return Subscription::fromArray($response['data']);
    }

    /**
     * Get mailbox, storage and Developer API usage
     *
     * @throws ZemailException
     */
    public function usage(): Usage
    {
        $response = $this->client->request('GET', '/api/account/usage');

        return Usage::fromArray($response['data']);
    }
}
