<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an outgoing movement would take a warehouse below zero.
 * Nothing is written when this is thrown: callers run inside a transaction.
 */
class InsufficientStock extends RuntimeException
{
    public static function for(string $itemName, string $warehouseName, int $onHand, int $requested): self
    {
        return new self("Not enough {$itemName} in {$warehouseName}: {$onHand} on hand, {$requested} needed.");
    }
}
