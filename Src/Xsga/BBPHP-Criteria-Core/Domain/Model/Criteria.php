<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Model;

final class Criteria
{
    /**
     * @param Filter[] $filters
     * @param Order[]  $orders
     */
    public function __construct(
        private readonly array $filters,
        private readonly array $orders,
        private readonly Pagination $pagination
    ) {
    }

    /** @return Filter[] */
    public function filters(): array
    {
        return $this->filters;
    }

    /** @return Order[] */
    public function orders(): array
    {
        return $this->orders;
    }

    public function pagination(): Pagination
    {
        return $this->pagination;
    }
}
