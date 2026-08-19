<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Services;

use Xsga\BBPHP\Criteria\Core\Domain\Model\Pagination;

final class GetPaginationService
{
    public function get(array $requestData, int $maxResults): Pagination
    {
        $limit = match (isset($requestData['limit'])) {
            true  => (int)$requestData['limit'] > $maxResults ? $maxResults : (int)$requestData['limit'],
            false => $maxResults
        };

        $offset = match (isset($requestData['offset'])) {
            true  => (int)$requestData['offset'],
            false => 0
        };

        return new Pagination($limit, $offset);
    }
}
