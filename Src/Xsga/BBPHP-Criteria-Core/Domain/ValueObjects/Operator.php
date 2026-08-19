<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\FilterOperatorNotValidException;
use Xsga\BBPHP\Criteria\Core\Domain\Operators;

final class Operator
{
    // TODO: Delete this code?.
    private const int ERROR_CRITERIA_OPERATOR_NOT_VALID = 1046;

    private readonly string $operator;

    public function __construct(string $operator)
    {
        $this->operator = $this->validate($operator);
    }

    public function value(): string
    {
        return $this->operator;
    }

    private function validate(string $operator): string
    {
        $validOperator = match (strtolower($operator)) {
            Operators::EQUAL->value         => '=',
            Operators::NOT_EQUAL->value     => '<>',
            Operators::LIKE->value          => 'CONTAINS',
            Operators::GREATER->value       => '>',
            Operators::LESS->value          => '<',
            Operators::GREATER_EQUAL->value => '>=',
            Operators::LESS_EQUAL->value    => '<=',
            Operators::IN->value            => 'IN',
            default                         => null
        };

        if ($validOperator === null) {
            /*
            throw new FilterOperatorNotValidException(
                "Error, operator '$operator' not valid. Valid operators: eq, ne, lk, gt, lt, ge, le, in",
                self::ERROR_CRITERIA_OPERATOR_NOT_VALID,
                null,
                [1 => $operator]
            );
            */
            throw new FilterOperatorNotValidException(
                "Error, operator '$operator' not valid. Valid operators: eq, ne, lk, gt, lt, ge, le, in",
                self::ERROR_CRITERIA_OPERATOR_NOT_VALID
            );
        }

        return $validOperator;
    }
}
