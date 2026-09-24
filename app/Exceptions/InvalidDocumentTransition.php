<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a document is asked to move to a status its current status does
 * not allow, e.g. receiving a purchase order that was never approved.
 * The message is written for the person who clicked the button.
 */
class InvalidDocumentTransition extends RuntimeException
{
    public static function from(string $number, string $currentStatus, string $action): self
    {
        return new self("{$number} is {$currentStatus}, so it cannot be {$action}.");
    }
}
