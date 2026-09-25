# Technical documentation of BBPHP Criteria Core

## 1. Overview

BBPHP Criteria Core is a PHP library for building typed, safe, and reusable query criteria. Its purpose is to transform an input structure, typically an HTTP request or an associative array, into a `Criteria` object that encapsulates filters, sorting, and pagination.

The library is designed as a domain-agnostic core: it does not execute queries and does not depend on a specific database technology. Its main responsibility is to validate and normalize search criteria before another layer of the system consumes them.

### Purpose

- Validate comparison operators in a closed set.
- Normalize input keys to lowercase.
- Build domain objects instead of concatenated strings.
- Avoid formatting and value-type errors across project layers.
- Provide a consistent contract for searching, ordering, and paginating data.

### Current project scope

The current repository includes the functional core of the library, with these main pieces:

- `CriteriaService`: main orchestrator.
- `GetFiltersService`: filter parsing and validation.
- `GetOrderService`: sorting parsing and validation.
- `GetPaginationService`: pagination construction.
- Domain models and value objects.
- Enums for operators, value types, and ordering types.
- Specific exceptions for validations.

---

## 2. Project structure

```text
Src/
└── Xsga/
    └── BBPHP-Criteria-Core/
        ├── Application/
        │   ├── Mappers/
        │   │   └── CriteriaToArray.php
        │   └── Services/
        │       └── CriteriaService.php
        └── Domain/
            ├── Exceptions/
            │   ├── FieldNotValidException.php
            │   ├── FilterNotValidException.php
            │   ├── FilterOperatorNotValidException.php
            │   ├── OrderByNotValidException.php
            │   ├── OrderTypeNotValidException.php
            │   ├── ValueNotValidException.php
            │   └── ValueTypeNotValidException.php
            ├── Model/
            │   ├── Criteria.php
            │   ├── Filter.php
            │   ├── Order.php
            │   └── Pagination.php
            ├── Services/
            │   ├── GetFiltersService.php
            │   ├── GetOrderService.php
            │   └── GetPaginationService.php
            ├── ValueObjects/
            │   ├── Field.php
            │   ├── Operator.php
            │   ├── OrderType.php
            │   ├── Value.php
            │   ├── ValueType.php
            │   └── ...
            ├── Operators.php
            ├── OrdersType.php
            └── ValuesType.php
```

### PHP packages

The base namespace of the project is:

```php
Xsga\BBPHP\Criteria\Core\
```

and the Composer autoload is declared as:

```json
"psr-4": {
  "Xsga\\BBPHP\\Criteria\\Core\\": "Src/Xsga/BBPHP-Criteria-Core/"
}
```

---

## 3. Main components

### 3.1 `CriteriaService`

This is the entry point of the core. It receives an associative array with search and pagination parameters, normalizes keys, and returns a `Criteria` object.

```php
final class CriteriaService
{
    public function __construct(
        private readonly GetFiltersService $getFiltersService,
        private readonly GetOrderService $getOrderService,
        private readonly GetPaginationService $getPaginationService,
        private readonly int $maxResults
    ) {
    }

    public function getCriteria(array $requestData): Criteria
    {
        $requestData = array_change_key_case($requestData, CASE_LOWER);

        $filters    = $this->getFiltersService->get($requestData);
        $order      = $this->getOrderService->get($requestData);
        $pagination = $this->getPaginationService->get($requestData, $this->maxResults);

        return new Criteria(filters: $filters, orders: $order, pagination: $pagination);
    }
}
```

### 3.2 `Criteria`

This is the root aggregate of the domain, composed of:

- `filters`: set of filters
- `orders`: sorting criteria
- `pagination`: pagination data

```php
final class Criteria
{
    public function __construct(
        private readonly array $filters,
        private readonly array $orders,
        private readonly Pagination $pagination
    ) {
    }
}
```

### 3.3 `Filter`

Represents an individual search condition. It validates:

- that the field is not empty,
- that the operator exists,
- that the value type is valid,
- that incompatible operators are not mixed, such as `in` with `date` or `lk` with `date`.

```php
final class Filter
{
    public function __construct(string $field, string $operator, string $value, string $valueType)
}
```

The `value()` method converts the value to `DateTime` when the type is `date`.

### 3.4 `Order`

Represents an ordering by field and type.

```php
final class Order
{
    public function __construct(string $field, string $type)
}
```

### 3.5 `Pagination`

Groups `limit` and `offset` and is built by `GetPaginationService`.

```php
final class Pagination
{
    public function __construct(
        private readonly int $limit,
        private readonly int $offset
    ) {
    }
}
```

---

## 4. Supported operators

The library supports the following operators:

| Short operator | Internal mapping | Meaning |
| --- | --- | --- |
| `eq` | `=` | equal |
| `ne` | `<>` | not equal |
| `lk` | `CONTAINS` | like / contains |
| `gt` | `>` | greater than |
| `lt` | `<` | less than |
| `ge` | `>=` | greater or equal |
| `le` | `<=` | less or equal |
| `in` | `IN` | included in a set |

Validation happens in `Operator`, and any unrecognized operator throws a specific exception.

---

## 5. Value and ordering types

### Supported value types

```php
enum ValuesType: string
{
    case STRING = 'string';
    case DATE   = 'date';
}
```

### Supported ordering types

```php
enum OrdersType: string
{
    case ASC  = 'asc';
    case DESC = 'desc';
}
```

Ordering values are normalized to uppercase internally (`ASC`, `DESC`).

---

## 6. Input format

The library expects an array with normalized keys or with typical query string names.

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

Multiple filters are allowed with commas:

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

If `limit` is not specified, `GetPaginationService` takes the value passed as `maxResults`. If `offset` is not specified, it uses `0`.

---

## 7. Execution flow

```text
request array
    ↓
CriteriaService::getCriteria()
    ↓
array_change_key_case(..., CASE_LOWER)
    ↓
GetFiltersService::get()
GetOrderService::get()
GetPaginationService::get()
    ↓
new Criteria(filters, orders, pagination)
```

### Full example

```php
use Psr\Log\NullLogger;
use Xsga\BBPHP\Criteria\Core\Application\Services\CriteriaService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetFiltersService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetOrderService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetPaginationService;

$logger = new NullLogger();

$service = new CriteriaService(
    new GetFiltersService($logger),
    new GetOrderService($logger),
    new GetPaginationService(),
    50
);

$criteria = $service->getCriteria([
    'filter' => 'status:eq:active,createdAt:ge:2025-01-01:date',
    'order_by' => 'createdAt:DESC',
    'limit' => 10,
    'offset' => 0,
]);
```

---

## 8. Validations and exceptions

The library throws typed exceptions when it detects a formatting or value problem. It includes validation for:

- empty fields,
- invalid operators,
- invalid value types,
- malformed filter syntax,
- malformed ordering syntax,
- incompatible operator and value type combinations.

Some relevant exceptions are:

- `FieldNotValidException`
- `FilterNotValidException`
- `FilterOperatorNotValidException`
- `OrderByNotValidException`
- `OrderTypeNotValidException`
- `ValueTypeNotValidException`

---

## 9. Mapping to array output

The project includes `CriteriaToArray` to represent a `Criteria` object as a readable array.

```php
final class CriteriaToArray
{
    public function convert(Criteria $criteria): array
    {
        return [
            'filters'    => $this->getFilters($criteria),
            'order'      => $this->getOrders($criteria),
            'pagination' => $this->getPagination($criteria),
        ];
    }
}
```

This can produce output similar to:

```php
[
    'filters' => ['status = active', 'createdAt >= 2025-01-01 00:00:00'],
    'order' => ['createdAt DESC'],
    'pagination' => ['limit' => 10, 'offset' => 0],
]
```

---

## 10. Design principles

The library relies on several design principles:

- Value Objects for typed validation.
- Enums to limit valid values.
- Clear separation between parsing services and domain models.
- Immutability in domain objects.
- Early and explicit validation of inputs.

---

## 11. Important considerations

### What the library does

- Builds a criteria model for queries.
- Validates and normalizes input.
- Structures filters, sorting, and pagination.
- Allows query logic to be reused across different layers of the system.

### What it does not do

- It does not execute queries against a database.
- It does not know concrete domain entities.
- It does not provide persistence infrastructure or ORM adapters by itself.
- It is not a complete HTTP framework or application layer.

---

## 12. Current status

This repository represents the criteria core of the BBPHP project. Its current focus is to provide a solid base for building search criteria from an array or an HTTP request, while keeping the logic typed and validated in the domain layer.

It is a reusable, extensible library focused on consistency in query validation.
