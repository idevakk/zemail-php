# Zemail PHP SDK

The official PHP SDK for the [Zemail Developer API](https://zemail.me/api-docs).

## Installation

Install the package via composer:

```bash
composer require zemailme/zemail-php
```

Requires PHP 8.2+ and Guzzle.

## Usage

First, initialize the `Client` with your API key.

```php
use Zemail\Client;

$client = new Client('zm_live_your_api_key_here');
```

### Mailboxes

**List mailboxes:**
```php
$mailboxes = $client->mailboxes()->list();
foreach ($mailboxes->data as $mailbox) {
    echo $mailbox->address . "\n";
}
```

**Create a mailbox:**
```php
$mailbox = $client->mailboxes()->create([
    'type' => 'random'
]);

echo "Created: " . $mailbox->address;
```

**Delete a mailbox:**
```php
$client->mailboxes()->delete($mailbox->id);
```

### Emails

**List emails in a mailbox:**
```php
$emails = $client->mailboxes()->emails()->list($mailbox->id);
foreach ($emails->data as $summary) {
    echo $summary->subject . "\n";
}
```

**Get full email details:**
```php
$email = $client->mailboxes()->emails()->get($mailbox->id, $emailId);
echo $email->bodyHtml;
```

### Account Details

```php
$account = $client->account()->get();
$usage = $client->account()->usage();
```

## Exception Handling

The SDK throws exceptions that inherit from `Zemail\Exceptions\ZemailException`.

- `AuthenticationException` (401, 403)
- `NotFoundException` (404)
- `ValidationException` (422 validation_failed)
- `RateLimitException` (429)

```php
use Zemail\Exceptions\ValidationException;

try {
    $client->mailboxes()->create(['type' => 'custom']); // Missing domain and username
} catch (ValidationException $e) {
    print_r($e->errors);
}
```

## Testing

```bash
composer test
```