<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

final class Limit
{
    private readonly int $limit;

    public function __construct(int $limit)
    {
        $this->limit = match ($limit < 0) {
            true => 0,
            false => $limit
        };
    }

    public function value(): int
    {
        return $this->limit;
    }
}
