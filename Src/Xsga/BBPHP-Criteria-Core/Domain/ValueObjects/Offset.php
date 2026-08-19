<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

final class Offset
{
    private readonly int $offset;

    public function __construct(int $offset)
    {
        $this->offset = match ($offset < 0) {
            true => 0,
            false => $offset
        };
    }

    public function value(): int
    {
        return $this->offset;
    }
}
