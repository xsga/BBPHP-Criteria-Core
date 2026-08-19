<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Model;

use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\Limit;
use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\Offset;

final class Pagination
{
    private readonly Limit $limit;
    private readonly Offset $offset;

    public function __construct(int $limit, int $offset)
    {
        $this->limit  = new Limit($limit);
        $this->offset = new Offset($offset);
    }

    public function limit(): int
    {
        return $this->limit->value();
    }

    public function offset(): int
    {
        return $this->offset->value();
    }
}
