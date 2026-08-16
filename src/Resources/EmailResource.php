<?php

namespace Zemail\Resources;

use Zemail\Client;
use Zemail\Exceptions\ZemailException;
use Zemail\Models\EmailDetail;
use Zemail\Models\EmailSummary;
use Zemail\Models\PaginatedList;

class EmailResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List emails for a mailbox
     *
     * @throws ZemailException
     */
    public function list(int $mailboxId, int $page = 1, ?int $limit = null, ?string $search = null): PaginatedList
    {
        $query = ['page' => $page];
        if ($limit !== null) {
            $query['limit'] = $limit;
        }
        if ($search !== null) {
            $query['search'] = $search;
        }

        $response = $this->client->request('GET', "/api/mailboxes/{$mailboxId}/emails", [
            'query' => $query,
        ]);

        $emails = array_map(function ($item) {
            return EmailSummary::fromArray($item);
        }, $response['data'] ?? []);

        return new PaginatedList(
            $emails,
            $response['has_more'] ?? false,
            $response['next_cursor'] ?? null,
            $response['meta'] ?? []
        );
    }

    /**
     * Get full email details
     *
     * @throws ZemailException
     */
    public function get(int $mailboxId, int $emailId): EmailDetail
    {
        $response = $this->client->request('GET', "/api/mailboxes/{$mailboxId}/emails/{$emailId}");

        return EmailDetail::fromArray($response['data']);
    }

    /**
     * Delete an email
     *
     * @throws ZemailException
     */
    public function delete(int $mailboxId, int $emailId): bool
    {
        $response = $this->client->request('DELETE', "/api/mailboxes/{$mailboxId}/emails/{$emailId}");

        return ($response['data']['deleted'] ?? $response['deleted'] ?? false) === true;
    }

    /**
     * Mark an email as read
     *
     * @return bool Returns the new is_read state (which is true)
     *
     * @throws ZemailException
     */
    public function markAsRead(int $mailboxId, int $emailId): bool
    {
        $response = $this->client->request('POST', "/api/mailboxes/{$mailboxId}/emails/{$emailId}/mark-read");

        return $response['is_read'] ?? true;
    }

    /**
     * Create a temporary attachment download URL
     *
     * @return array{url: string, expires_at: string}
     *
     * @throws ZemailException
     */
    public function getAttachmentDownloadUrl(int $mailboxId, int $emailId, string $attachmentId): array
    {
        $response = $this->client->request('POST', "/api/mailboxes/{$mailboxId}/emails/{$emailId}/attachments/{$attachmentId}/download-url");

        return [
            'url' => $response['url'],
            'expires_at' => $response['expires_at'],
        ];
    }
}
