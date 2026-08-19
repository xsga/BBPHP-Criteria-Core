<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Services;

use Psr\Log\LoggerInterface;
use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\FilterNotValidException;
use Xsga\BBPHP\Criteria\Core\Domain\Model\Filter;
use Xsga\BBPHP\Criteria\Core\Domain\ValuesType;

final class GetFiltersService
{
    // TODO: Delete this code?.
    private const int ERROR_FILTER_NOT_VALID = 1045;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /** @return Filter[] */
    public function get(array $requestData): array
    {
        if (!isset($requestData['filter'])) {
            return [];
        }

        $filtersUrl = (string)$requestData['filter'];

        $filtersArray = explode(',', $filtersUrl);
        $filtersArray = array_filter($filtersArray, 'strlen');

        $result = array_map(
            fn(string $urlFilter): Filter => $this->getFilter($urlFilter),
            $filtersArray
        );

        return $result;
    }

    private function getFilter(string $urlFilter): Filter
    {
        $filterArray = explode(':', $urlFilter);
        $filterArray = array_filter($filterArray, 'strlen');

        $this->validateFilterFormat($filterArray, $urlFilter);

        $filter = new Filter(
            $filterArray[0],
            $filterArray[1],
            $filterArray[2],
            $filterArray[3] ?? ValuesType::STRING->value
        );

        return $filter;
    }

    private function validateFilterFormat(array $filterArray, string $filter): void
    {
        if (count($filterArray) !== 3 && count($filterArray) !== 4) {
            $errorMsg = "Filter not valid. Valid format: 'field:operator:value:valueType(optional)'";

            $this->logger->error($errorMsg, [
                'event' => 'error.criteria.get_filters.filter_not_valid',
                'filter' => $filter
            ]);

            //throw new FilterNotValidException($errorMsg, self::ERROR_FILTER_NOT_VALID, null, [1 => $filter]);
            throw new FilterNotValidException($errorMsg, self::ERROR_FILTER_NOT_VALID);
        }
    }
}
