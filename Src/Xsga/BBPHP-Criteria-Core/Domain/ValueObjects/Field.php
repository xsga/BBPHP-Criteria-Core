<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\FieldNotValidException;

final class Field
{
    private const int ERROR_CRITERIA_FIELD_NOT_VALID = 1048;

    private readonly string $field;

    public function __construct(string $field)
    {
        $this->field = $this->validate(trim($field));
    }

    public function value(): string
    {
        return $this->field;
    }

    private function validate(string $field): string
    {
        if (empty($field)) {
            throw new FieldNotValidException('Error, empty criteria field', self::ERROR_CRITERIA_FIELD_NOT_VALID);
        }

        return $field;
    }
}
