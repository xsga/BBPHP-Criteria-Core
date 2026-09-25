<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Services;

use Psr\Log\LoggerInterface;
use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\OrderByNotValidException;
use Xsga\BBPHP\Criteria\Core\Domain\Model\Order;

final class GetOrderService
{
    private const int ERROR_ORDER_FIELD_NOT_VALID = 1043;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /** @return Order[] */
    public function get(array $requestData): array
    {
        if (!isset($requestData['order_by'])) {
            return [];
        }

        $sortFieldsUrl = (string)$requestData['order_by'];

        $sortFieldsArray = explode(',', $sortFieldsUrl);
        $sortFieldsArray = array_filter($sortFieldsArray, 'strlen');

        $result = array_map(
            fn(string $urlSortField): Order => $this->getOrderField($urlSortField),
            $sortFieldsArray
        );

        return $result;
    }

    private function getOrderField(string $urlSortField): Order
    {
        $sortFieldArray = explode(':', $urlSortField);
        $sortFieldArray = array_filter($sortFieldArray, 'strlen');

        $this->validateOrderFieldFormat($sortFieldArray, $urlSortField);

        $order = new Order($sortFieldArray[0], $sortFieldArray[1]);

        return $order;
    }

    private function validateOrderFieldFormat(array $sortFieldArray, string $sortField): void
    {
        if (count($sortFieldArray) !== 2) {
            $errorMsg = "Order field not valid. Valid format: 'field:order_type'";

            $this->logger->error($errorMsg, [
                'event' => 'error.criteria.get_orders.order_not_valid',
                'order_field' => $sortField
            ]);

            throw new OrderByNotValidException($errorMsg, self::ERROR_ORDER_FIELD_NOT_VALID, [1 => $sortField]);
        }
    }
}
