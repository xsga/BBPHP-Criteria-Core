<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Model;

use DateTime;
use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\FilterNotValidException;
use Xsga\BBPHP\Criteria\Core\Domain\Operators;
use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\Field;
use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\Operator;
use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\Value;
use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\ValueType;
use Xsga\BBPHP\Criteria\Core\Domain\ValuesType;

final class Filter
{
    private const int ERROR_FILTER_NOT_VALID = 1059;

    private readonly Field $field;
    private readonly Operator $operator;
    private readonly Value $value;
    private readonly ValueType $valueType;

    public function __construct(string $field, string $operator, string $value, string $valueType)
    {
        $this->validateOperatorAndValueType($operator, $valueType);

        $this->field     = new Field($field);
        $this->operator  = new Operator($operator);
        $this->value     = new Value($value);
        $this->valueType = new ValueType($valueType);
    }

    private function validateOperatorAndValueType(string $operator, string $valueType): void
    {
        if (strtolower($operator) === Operators::IN->value && strtolower($valueType) === ValuesType::DATE->value) {
            $errorMsg = 'The IN operator cannot be used with DATETIME values';
            throw new FilterNotValidException($errorMsg, self::ERROR_FILTER_NOT_VALID);
        }

        if (strtolower($operator) === Operators::LIKE->value && strtolower($valueType) === ValuesType::DATE->value) {
            $errorMsg = 'The LIKE operator cannot be used with DATETIME values';
            throw new FilterNotValidException($errorMsg, self::ERROR_FILTER_NOT_VALID);
        }
    }

    public function field(): string
    {
        return $this->field->value();
    }

    public function operator(): string
    {
        return $this->operator->value();
    }

    public function value(): string|DateTime
    {
        if ($this->valueType->value() === ValuesType::DATE->value) {
            $dateValue = new DateTime($this->value->value());

            return match ($this->operator->value()) {
                '>=', '<' => $dateValue->setTime(0, 0, 0),
                '>', '<=' => $dateValue->setTime(23, 59, 59),
                default => $dateValue,
            };
        }

        return $this->value->value();
    }
}
