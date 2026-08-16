<?php

namespace Zemail\Exceptions;

use Throwable;

class ValidationException extends ZemailException
{
    /**
     * @var array<string, array<string>>
     */
    public readonly array $errors;

    public function __construct(
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null,
        ?string $zemailErrorCode = 'validation_failed',
        ?string $param = null,
        ?string $requestId = null,
        array $errors = []
    ) {
        parent::__construct($message, $code, $previous, $zemailErrorCode, $param, $requestId);
        $this->errors = $errors;
    }
}
