<?php

namespace Zemail\Exceptions;

use Exception;
use Throwable;

class ZemailException extends Exception
{
    public readonly ?string $zemailErrorCode;

    public readonly ?string $param;

    public readonly ?string $requestId;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $zemailErrorCode = null,
        ?string $param = null,
        ?string $requestId = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->zemailErrorCode = $zemailErrorCode;
        $this->param = $param;
        $this->requestId = $requestId;
    }
}
