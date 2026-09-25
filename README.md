# BBPHP Criteria Core

A PHP library for building typed, validated, and reusable search criteria.

## Description

BBPHP Criteria Core centralizes the logic needed to:

- parse filters from an array or query string,
- validate operators and value types,
- sort results by field,
- manage pagination using `limit` and `offset`,
- return an immutable `Criteria` model ready to be consumed by upper layers.

The library is designed as an infrastructure-agnostic core: it does not execute SQL queries and does not depend on a specific ORM; it focuses on validating and structuring the query criteria.

## Features

- Validation of supported operators: `eq`, `ne`, `lk`, `gt`, `lt`, `ge`, `le`, `in`
- Support for value types: `string` and `date`
- Validation of sorting: `ASC` and `DESC`
- Construction of `Criteria`, `Filter`, `Order`, and `Pagination`
- Lowercase key normalization
- Typed exceptions for formatting and validation errors
- PSR-4 autoload compatible with Composer

## Requirements

- PHP 8.4+
- Composer

## Installation

```bash
composer require xsga/bbphp-criteria-core
```

## Basic usage

```php
use Psr\Log\NullLogger;
use Xsga\BBPHP\Criteria\Core\Application\Services\CriteriaService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetFiltersService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetOrderService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetPaginationService;

$logger = new NullLogger();

$criteriaService = new CriteriaService(
    new GetFiltersService($logger),
    new GetOrderService($logger),
    new GetPaginationService(),
    50
);

$criteria = $criteriaService->getCriteria([
    'filter' => 'status:eq:active,createdAt:ge:2025-01-01:date',
    'order_by' => 'createdAt:DESC',
    'limit' => 10,
    'offset' => 0,
]);
```

## Input format

### Filters

```text
filter=field:operator:value[:type]
```

Examples:

```text
filter=status:eq:active
filter=createdAt:ge:2025-01-01:date
filter=id:in:1|2|3
filter=email:lk:john
```

Multiple filters:

```text
filter=status:eq:active,createdAt:ge:2025-01-01:date
```

### Ordering

```text
order_by=field:type
```

Examples:

```text
order_by=createdAt:DESC
order_by=name:ASC,createdAt:DESC
```

### Pagination

```text
limit=20&offset=0
```

## Internal structure

```text
Src/Xsga/BBPHP-Criteria-Core/
├── Application/
│   ├── Mappers/
│   │   └── CriteriaToArray.php
│   └── Services/
│       └── CriteriaService.php
├── Domain/
│   ├── Exceptions/
│   ├── Model/
│   │   ├── Criteria.php
│   │   ├── Filter.php
│   │   ├── Order.php
│   │   └── Pagination.php
│   ├── Services/
│   │   ├── GetFiltersService.php
│   │   ├── GetOrderService.php
│   │   └── GetPaginationService.php
│   ├── ValueObjects/
│   ├── Operators.php
│   ├── OrdersType.php
│   └── ValuesType.php
└── ...
```

## Design principles

- Value Objects for typed validation
- Enums to control operators and types
- Immutable domain models
- Clear separation between parsing services and criterion representation
- Early validation to prevent downstream business errors

## Additional documentation

For a more detailed reference, see the technical documentation in [Doc/BBPHP-Criteria-Core.md](Doc/BBPHP-Criteria-Core.md).

## License

This project is licensed under the MIT License.
