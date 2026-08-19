<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\ValueTypeNotValidException;
use Xsga\BBPHP\Criteria\Core\Domain\ValuesType;

final class ValueType
{
    // TODO: Delete this code?.
    private const int ERROR_CRITERIA_VALUE_TYPE_NOT_VALID = 1057;

    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $this->validate($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    private function validate(string $value): string
    {
        $validType = match (strtolower($value)) {
            ValuesType::STRING->value => strtolower($value),
            ValuesType::DATE->value   => strtolower($value),
            default  => null
        };

        if ($validType === null) {
            throw new ValueTypeNotValidException(
                'Error, criteria value type not valid',
                self::ERROR_CRITERIA_VALUE_TYPE_NOT_VALID
            );
        }

        return $validType;
    }
}
