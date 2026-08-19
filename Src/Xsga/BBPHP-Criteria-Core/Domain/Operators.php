<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain;

enum Operators: string
{
    case EQUAL         = 'eq';
    case NOT_EQUAL     = 'ne';
    case LIKE          = 'lk';
    case GREATER       = 'gt';
    case LESS          = 'lt';
    case GREATER_EQUAL = 'ge';
    case LESS_EQUAL    = 'le';
    case IN            = 'in';
}
