<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Application\Mappers;

use DateTime;
use Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria;

final class CriteriaToArray
{
    /** @return array{filters:string[],order:string[],pagination:array{limit:int,offset:int}} */
    public function convert(Criteria $criteria): array
    {
        $filters = [];
        foreach ($criteria->filters() as $filter) {
            $value = $filter->value();
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $filters[] = sprintf('%s %s %s', $filter->field(), $filter->operator(), $value);
        }

        $orders = [];
        foreach ($criteria->orders() as $order) {
            $orders[] = sprintf('%s %s', $order->field(), $order->type());
        }

        $pagination = [
            'limit' => $criteria->pagination()->limit(),
            'offset' => $criteria->pagination()->offset(),
        ];

        return [
            'filters' => $filters,
            'order' => $orders,
            'pagination' => $pagination,
        ];
    }
}
