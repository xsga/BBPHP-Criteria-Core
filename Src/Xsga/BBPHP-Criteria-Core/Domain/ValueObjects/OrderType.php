<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Core\Domain\ValueObjects;

use Xsga\BBPHP\Criteria\Core\Domain\Exceptions\OrderTypeNotValidException;
use Xsga\BBPHP\Criteria\Core\Domain\OrdersType;

final class OrderType
{
    // TODO: Delete this code?.
    private const int ERROR_ORDER_TYPE_NOT_VALID = 1044;

    private readonly string $type;

    public function __construct(string $type)
    {
        $this->type = $this->validate($type);
    }

    public function value(): string
    {
        return $this->type;
    }

    private function validate(string $type): string
    {
        $validType = match (strtolower($type)) {
            OrdersType::ASC->value  => 'ASC',
            OrdersType::DESC->value => 'DESC',
            default => null
        };

        if ($validType === null) {
            $errorMsg = "Error, order type '$type' not valid. Valid types: ASC, DESC";
            //throw new OrderTypeNotValidException($errorMsg, self::ERROR_ORDER_TYPE_NOT_VALID, null, [1 => $type]);
            throw new OrderTypeNotValidException($errorMsg, self::ERROR_ORDER_TYPE_NOT_VALID);
        }

        return $validType;
    }
}
