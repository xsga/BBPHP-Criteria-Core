<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\Model;

use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\Field;
use Xsga\BBPHP\Criteria\Core\Domain\ValueObjects\OrderType;

final class Order
{
    private readonly Field $field;
    private readonly OrderType $type;

    public function __construct(string $field, string $type)
    {
        $this->field = new Field($field);
        $this->type  = new OrderType($type);
    }

    public function field(): string
    {
        return $this->field->value();
    }

    public function type(): string
    {
        return $this->type->value();
    }
}
