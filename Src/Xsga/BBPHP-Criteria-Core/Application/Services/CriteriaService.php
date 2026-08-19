<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Application\Services;

use Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetFiltersService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetOrderService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetPaginationService;

final class CriteriaService
{
    public function __construct(
        private readonly GetFiltersService $getFiltersService,
        private readonly GetOrderService $getOrderService,
        private readonly GetPaginationService $getPaginationService,
        private readonly int $maxResults
    ) {
    }

    public function getCriteria(array $requestData): Criteria
    {
        $requestData = array_change_key_case($requestData, CASE_LOWER);

        $filters    = $this->getFiltersService->get($requestData);
        $order      = $this->getOrderService->get($requestData);
        $pagination = $this->getPaginationService->get($requestData, $this->maxResults);

        $criteria = new Criteria($filters, $order, $pagination);

        return $criteria;
    }
}
