<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\ValueNotValidException;

final class Value
{
    private const int ERROR_CRITERIA_VALUE_NOT_VALID = 1049;

    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $this->validate(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    private function validate(string $value): string
    {
        if ($value === '') {
            throw new ValueNotValidException('Error, criteria value empty', self::ERROR_CRITERIA_VALUE_NOT_VALID);
        }

        return $value;
    }
}
