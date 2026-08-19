<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain;

enum OrdersType: string
{
    case ASC  = 'asc';
    case DESC = 'desc';
}
