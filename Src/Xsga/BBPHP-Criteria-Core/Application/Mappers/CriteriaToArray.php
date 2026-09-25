<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Application\Mappers;

use DateTime;
use Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria;

final class CriteriaToArray
{
    private const string DATE_TIME_MASK = 'd-m-Y H:i:s';

    /** @return array{filters:string[],order:string[],pagination:array{limit:int,offset:int}} */
    public function convert(Criteria $criteria): array
    {
        return [
            'filters'    => $this->getFilters($criteria),
            'order'      => $this->getOrders($criteria),
            'pagination' => $this->getPagination($criteria),
        ];
    }

    /** @return string[] */
    private function getFilters(Criteria $criteria): array
    {
        $filters = [];

        foreach ($criteria->filters() as $filter) {
            $value = $filter->value();

            if ($value instanceof DateTime) {
                $value = $value->format(self::DATE_TIME_MASK);
            }

            $filters[] = sprintf('%s %s %s', $filter->field(), $filter->operator(), $value);
        }

        return $filters;
    }

    /** @return string[] */
    private function getOrders(Criteria $criteria): array
    {
        $orders = [];

        foreach ($criteria->orders() as $order) {
            $orders[] = sprintf('%s %s', $order->field(), $order->type());
        }

        return $orders;
    }

    /** @return array{limit:int,offset:int} */
    private function getPagination(Criteria $criteria): array
    {
        return [
            'limit'  => $criteria->pagination()->limit(),
            'offset' => $criteria->pagination()->offset(),
        ];
    }
}
