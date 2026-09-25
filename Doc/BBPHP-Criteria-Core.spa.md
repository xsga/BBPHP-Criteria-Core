# Documentación técnica de BBPHP Criteria Core

## 1. Visión general

BBPHP Criteria Core es una librería PHP para construir criterios de consulta de forma tipada, segura y reutilizable. Su objetivo es transformar una estructura de entrada, normalmente una petición HTTP o un array asociativo, en un objeto `Criteria` que encapsula filtros, ordenación y paginación.

La librería está diseñada para ser un núcleo agnóstico del dominio: no ejecuta consultas ni depende de una base de datos concreta. Su responsabilidad principal es validar y normalizar los criterios de búsqueda antes de que otra capa del sistema los consuma.

### Propósito

- Validar operadores de comparación de forma cerrada.
- Normalizar claves de entrada a minúsculas.
- Construir objetos de dominio en lugar de strings concatenados.
- Evitar errores de formato y tipos de valor en cada capa del proyecto.
- Ofrecer un contrato consistente para buscar, ordenar y paginar datos.

### Alcance actual del proyecto

El repositorio actual incluye el núcleo funcional de la librería, con estas piezas principales:

- `CriteriaService`: orquestador principal.
- `GetFiltersService`: parseo y validación del filtro.
- `GetOrderService`: parseo y validación de la ordenación.
- `GetPaginationService`: construcción de la paginación.
- Modelos y value objects del dominio.
- Enums para operadores, tipos de valor y tipos de ordenación.
- Excepciones específicas para validaciones.

---

## 2. Estructura del proyecto

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

### Paquetes PHP

El namespace base del proyecto es:

```php
Xsga\BBPHP\Criteria\Core\
```

y el autoload en Composer está declarado como:

```json
"psr-4": {
  "Xsga\\BBPHP\\Criteria\\Core\\": "Src/Xsga/BBPHP-Criteria-Core/"
}
```

---

## 3. Componentes principales

### 3.1 `CriteriaService`

Es el punto de entrada del núcleo. Recibe un array asociativo con los parámetros de paginación y búsqueda, normaliza claves y devuelve un objeto `Criteria`.

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

Es el agregado raíz del dominio, compuesto por:

- `filters`: conjunto de filtros
- `orders`: conjunto de ordenaciones
- `pagination`: datos de paginación

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

Representa una condición de búsqueda individual. Valida:

- que el campo no esté vacío,
- que el operador exista,
- que el tipo de valor sea válido,
- que no se mezclen operadores incompatibles como `in` con `date` o `lk` con `date`.

```php
final class Filter
{
    public function __construct(string $field, string $operator, string $value, string $valueType)
}
```

El método `value()` convierte el valor a `DateTime` cuando el tipo es `date`.

### 3.4 `Order`

Representa una ordenación por campo y tipo.

```php
final class Order
{
    public function __construct(string $field, string $type)
}
```

### 3.5 `Pagination`

Agrupa `limit` y `offset`, y se construye desde `GetPaginationService`.

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

## 4. Operadores soportados

La librería soporta los siguientes operadores:

| Operador corto | Mapeo interno | Significado |
| --- | --- | --- |
| `eq` | `=` | igual |
| `ne` | `<>` | distinto |
| `lk` | `CONTAINS` | parecido / contiene |
| `gt` | `>` | mayor que |
| `lt` | `<` | menor que |
| `ge` | `>=` | mayor o igual |
| `le` | `<=` | menor o igual |
| `in` | `IN` | incluido en conjunto |

La validación se hace en `Operator` y cualquier operador no reconocido lanza una excepción específica.

---

## 5. Tipos de valor y ordenación

### Tipos de valor admitidos

```php
enum ValuesType: string
{
    case STRING = 'string';
    case DATE   = 'date';
}
```

### Tipos de ordenación admitidos

```php
enum OrdersType: string
{
    case ASC  = 'asc';
    case DESC = 'desc';
}
```

Las ordenaciones se normalizan a mayúsculas internamente (`ASC`, `DESC`).

---

## 6. Formato de entrada

La librería espera un array con claves normalizadas o con nombres típicos de query string.

### Filtros

```text
filter=campo:operador:valor[:tipo]
```

Ejemplos:

```text
filter=status:eq:active
filter=createdAt:ge:2025-01-01:date
filter=id:in:1|2|3
filter=email:lk:john
```

Se permiten múltiples filtros separados por comas:

```text
filter=status:eq:active,createdAt:ge:2025-01-01:date
```

### Ordenación

```text
order_by=campo:tipo
```

Ejemplos:

```text
order_by=createdAt:DESC
order_by=name:ASC,createdAt:DESC
```

### Paginación

```text
limit=20&offset=0
```

Si no se especifica `limit`, `GetPaginationService` toma el valor pasado como `maxResults`. Si no se especifica `offset`, usa `0`.

---

## 7. Flujo de trabajo

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

### Ejemplo completo

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

## 8. Validaciones y excepciones

La librería lanza excepciones tipadas cuando se detecta un error de formato o de valor. Incluye validaciones para:

- campo vacío,
- operador inválido,
- tipo de valor inválido,
- formato incorrecto del filtro,
- formato incorrecto de la ordenación,
- incompatibilidad de operador y tipo de valor.

Algunas excepciones relevantes:

- `FieldNotValidException`
- `FilterNotValidException`
- `FilterOperatorNotValidException`
- `OrderByNotValidException`
- `OrderTypeNotValidException`
- `ValueTypeNotValidException`

---

## 9. Mapeo de salida a array

El proyecto incluye `CriteriaToArray` para representar el objeto `Criteria` en un array legible.

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

Esto resulta en una salida similar a:

```php
[
    'filters' => ['status = active', 'createdAt >= 2025-01-01 00:00:00'],
    'order' => ['createdAt DESC'],
    'pagination' => ['limit' => 10, 'offset' => 0],
]
```

---

## 10. Principios de diseño

La librería se apoya en varios principios de diseño:

- Value Objects para encapsular validaciones.
- Enums para limitar valores válidos.
- Separación de responsabilidades entre servicios de parseo y modelos.
- Inmutabilidad en los objetos del dominio.
- Validación temprana y explícita de entradas.

---

## 11. Consideraciones importantes

### Qué hace la librería

- Construye un modelo de criterios para consultas.
- Valida y normaliza la entrada.
- Estructura filtros, ordenaciones y paginación.
- Permite reutilizar la lógica de consultas en diferentes capas del sistema.

### Qué no hace

- No ejecuta queries contra una base de datos.
- No conoce entidades concretas del dominio.
- No ofrece infraestructura de persistencia ni adaptadores a ORM por sí misma.
- No es un framework ni un paquete HTTP completo.

---

## 12. Estado actual

Este repositorio representa el núcleo de criterios del proyecto BBPHP. Su enfoque actual es ofrecer una base sólida para construir criterios de búsqueda desde un array o una petición HTTP, manteniendo la lógica tipada y validada en la capa de dominio.

Es una librería reutilizable, extensible y orientada a consistencia en la validación de consultas.


**Componentes:**
- `campo`: Nombre del campo para ordenar.
- `tipo`: `asc` o `desc`.

**Ejemplos:**
```
# Orden simple
?order_by=createdAt:DESC

# Múltiples niveles de ordenación
?order_by=status:ASC,createdAt:DESC
```

#### Paginación (`limit` y `offset`)
```
?limit=N&offset=M
```

**Ejemplos:**
```
# Primera página, 20 resultados
?limit=20&offset=0

# Segunda página, 20 resultados
?limit=20&offset=20
```

#### Ejemplo completo
```
GET /users?filter=status:eq:active,createdAt:ge:2025-01-01:date&order_by=createdAt:DESC&limit=20&offset=0
```

### Operadores Soportados

| Abreviación | Operador SQL | Descripción | Ejemplo |
|:------------|:-------------|:------------|:--------|
| `eq` | `=` | Igual a | `status:eq:active` |
| `ne` | `<>` | Distinto de | `status:ne:deleted` |
| `lk` | `CONTAINS` | Contiene (like) | `email:lk:john` |
| `gt` | `>` | Mayor que | `age:gt:18` |
| `lt` | `<` | Menor que | `price:lt:100` |
| `ge` | `>=` | Mayor o igual | `createdAt:ge:2025-01-01:date` |
| `le` | `<=` | Menor o igual | `updatedAt:le:2025-12-31:date` |
| `in` | `IN` | Pertenece a conjunto | `id:in:1\|2\|3` |

### Restricciones de Operadores

| Operador | Tipo de Valor | Permitido | Razón |
|:---------|:--------------|:----------|:------|
| `in` | `date` | ❌ No | Doctrina no soporta IN con DateTime |
| `lk` | `date` | ❌ No | LIKE solo aplica a strings |
| Otros | `string` o `date` | ✅ Sí | - |

### Métodos Públicos de Servicios

#### `CriteriaService::getCriteria()`
```php
public function getCriteria(array $requestData): Criteria
```
- **Entrada:** Array asociativo con parámetros de request (ej. `$_GET` o request parsed).
- **Salida:** Objeto `Criteria` validado.
- **Excepciones:** 
  - `FilterNotValidException` (código 1045): Formato de filtro inválido.
  - `FilterOperatorNotValidException` (código 1046): Operador no soportado.
  - `OrderByNotValidException` (código 1043): Formato de orden inválido.
  - `OrderTypeNotValidException` (código 1044): Tipo de orden inválido.
  - `FieldNotValidException` (código 1048): Campo vacío.
  - `ValueTypeNotValidException` (código 1057): Tipo de valor no soportado.

#### `DoctrineCriteriaConverter::get()`
```php
public function get(Criteria $criteria): CollectionsCriteria
```
- **Entrada:** Objeto `Criteria` de dominio.
- **Salida:** `Doctrine\Common\Collections\Criteria` listo para usar en `EntityRepository::matching()`.

---

## 6. Dependencias

### Dependencias Internas del Proyecto
- **`Shared/Persistence`**: No tiene dependencia directa, pero los repositorios que usan Criteria dependen de entidades Doctrine.
- **`PSR-3 Logger`**: Inyectado en todos los servicios para logging de operaciones.

### Dependencias Externas
- **`doctrine/collections`**: Biblioteca de Doctrine que proporciona `Criteria` y `Expr` para consultas.
- **`psr/log`**: Interfaz estándar de logging PSR-3.
- **PHP 8.4+**: Uso de enums, readonly properties, named arguments, match expressions.

### Impacto de Dependencias
- **Doctrine Collections:** El módulo está acoplado a Doctrine en la capa de Infrastructure (converter), pero el dominio es agnóstico.
- **PSR-3 Logger:** Dependencia de infraestructura, fácilmente reemplazable por cualquier implementación PSR-3.
- **Enums PHP 8+:** Requiere PHP 8.1 mínimo; no retrocompatible con versiones anteriores.

---

## 7. Configuración

### Variables de Configuración
- **`maxResults`**: Límite por defecto de resultados cuando no se especifica `limit` en request.
  - **Tipo:** `int`
  - **Ubicación:** Inyectado en constructor de `CriteriaService` vía contenedor DI.
  - **Valor por defecto:** Configurado en [Config/Container/BlackBirdPhpContainer.php](Config/Container/BlackBirdPhpContainer.php) (típicamente `100` o según `.env`).

### Parámetros de Entorno
No hay variables de entorno específicas. La configuración se realiza en el contenedor DI:

```php
CriteriaService::class => DI\create(CriteriaService::class)->constructor(
    DI\get(LoggerInterface::class),
    DI\get(GetFiltersService::class),
    DI\get(GetOrderService::class),
    DI\get(GetPaginationService::class),
    DI\get('maxResults') // Valor inyectado desde config
)
```

### Catálogos de Valores Cerrados (Enums)

#### `Operators`
```php
enum Operators: string {
    case EQUAL         = 'eq';
    case NOT_EQUAL     = 'ne';
    case LIKE          = 'lk';
    case GREATER       = 'gt';
    case LESS          = 'lt';
    case GREATER_EQUAL = 'ge';
    case LESS_EQUAL    = 'le';
    case IN            = 'in';
}
```

#### `ValuesType`
```php
enum ValuesType: string {
    case STRING = 'string';
    case DATE   = 'date';
}
```

#### `OrdersType`
```php
enum OrdersType: string {
    case ASC  = 'asc';
    case DESC = 'desc';
}
```

---

## 8. Consideraciones de Seguridad

### Riesgos Potenciales
- **Inyección SQL:** Aunque Doctrine usa prepared statements, valores no sanitizados podrían causar problemas si se pasan directamente a DQL.
- **Enumeración de campos:** Un atacante podría enumerar nombres de campos válidos enviando diferentes nombres y observando errores.
- **DoS por filtros complejos:** Múltiples filtros o combinaciones costosas podrían saturar la BD.
- **Exposición de información en errores:** Las excepciones revelan estructura de datos (ej. "field 'password' not valid").

### Medidas Implementadas
- **Validación estricta de operadores:** Solo se permiten operadores del enum `Operators`.
- **Validación de tipos de valor:** Solo `string` y `date` están permitidos.
- **Validación cruzada Operador-TipoValor:** Previene combinaciones inválidas que podrían causar errores SQL.
- **Uso de Doctrine Criteria:** Doctrine genera SQL parametrizado, previniendo inyección.
- **Logging de todos los errores:** Los intentos de uso de operadores/valores inválidos se registran.
- **Límite máximo configurable:** `maxResults` previene consultas sin límite que puedan saturar memoria.

### Buenas Prácticas Recomendadas
1. **Whitelist de campos permitidos:** A nivel de repositorio, validar que los campos solicitados existan en la entidad y sean consultables (no exponer campos sensibles como `password`).
   
2. **Rate limiting en endpoints:** Limitar número de requests con criterios complejos por IP/usuario.

3. **Sanitización de valores:** Aunque Doctrine usa prepared statements, validar formatos de valores (ej. emails, UUIDs) antes de pasarlos a filtros.

4. **Paginación forzada:** No permitir requests sin `limit` o con `limit` > `maxResults`.

5. **Auditoría de búsquedas:** Loguear búsquedas complejas para detectar patrones de enumeración.

6. **Error handling genérico en API:** No exponer mensajes de excepción detallados al cliente (retornar mensajes genéricos y loguear detalles internamente).

7. **CORS y autenticación:** Asegurar que endpoints que usan criterios estén protegidos con autenticación y autorización adecuadas.

---

## 9. Rendimiento y Escalabilidad

### Factores que Afectan al Rendimiento
- **Número de filtros:** Más filtros = más condiciones WHERE = consultas más complejas.
- **Operador LIKE:** El operador `CONTAINS` (like `%valor%`) no usa índices, puede ser lento en tablas grandes.
- **Conversión de tipos:** La conversión de string a `DateTime` en `Filter::value()` añade overhead mínimo.
- **Logging intensivo:** Cada operación loguea múltiples líneas (debug level), puede impactar en alta concurrencia.

### Posibles Cuellos de Botella
- **Queries sin índices:** Si se filtran campos sin índices en BD, la performance será pobre.
- **Paginación con offsets grandes:** `OFFSET 10000` requiere que la BD "salte" 10000 filas, es ineficiente.
- **Múltiples ordenaciones:** Ordenar por varios campos puede ser costoso sin índices compuestos.
- **Operador IN con muchos valores:** `id:in:1|2|3|...|1000` genera query con 1000 parámetros.

### Estrategias de Escalabilidad
1. **Índices en BD:** Asegurar que todos los campos filtrados/ordenados tengan índices adecuados.

2. **Cursor-based pagination:** En lugar de offset, usar paginación basada en cursor (ej. `WHERE id > :lastId LIMIT 20`). Requiere modificación del módulo.

3. **Caché de resultados:** Para búsquedas frecuentes, cachear resultados con TTL corto (ej. Redis).

4. **Límite estricto de filtros/orders:** Configurar un máximo de filtros/ordenaciones permitidos (ej. max 5 filtros).

5. **Async processing:** Para consultas muy complejas, procesarlas asíncronamente y retornar un job ID.

6. **Read replicas:** Dirigir queries con criterios a réplicas de solo lectura.

7. **Query hints de Doctrine:** Añadir hints a las queries generadas para optimizaciones específicas de BD.

8. **Logging condicional:** Reducir logging a nivel INFO en producción, mantener DEBUG solo en desarrollo.

---

## 10. Logging, Monitorización y Errores

### Tipos de Logs Generados

**Estrategia de logging optimizado:** Este módulo sigue la filosofía "silence is success, noise is failure" (ver [ADR-012](../../../ADR/012-logging-optimizado-para-produccion.md)). Los logs DEBUG de parsing exitoso fueron eliminados. Solo se registran ERROR para formatos inválidos.

Todos los servicios usan `LoggerInterface` (PSR-3) con los siguientes niveles:

#### ERROR
- Formato de filtro inválido (`"Error, filter 'xyz' not valid. Valid format: ..."`).  
- Formato de orden inválido (`"Error, order field 'xyz' not valid. Valid format: ..."`).  

#### ~~DEBUG eliminado~~
- ~~Inicio de operaciones (`"Starting getting criteria object from request URL"`).~~ - **ELIMINADO** (flujo normal innecesario)
- ~~Normalización de datos (`"Request data normalized to lower case"`).~~ - **ELIMINADO** (operación normal)
- ~~Conversión de filtros/orders (`"Filters obtained successfully"`, `"Domain filter created successfully"`).~~ - **ELIMINADO** (éxito implícito)
- ~~Parsing de parámetros (`"Filters string provided => ..."`, `"Getting domain filter for => ..."`).~~ - **ELIMINADO** (detalle innecesario)
- ~~Conversión a Doctrine (`"Converting domain Criteria to Doctrine CollectionsCriteria"`).~~ - **ELIMINADO** (operación normal)

**Impacto:** Con ~1,000 requests con filtros/día, se eliminaron ~5,000 logs DEBUG innecesarios.

### Gestión de Errores

#### Excepciones del Módulo
Todas extienden de `GenericException` con códigos de error específicos:

| Excepción | Código | Contexto |
|:----------|:-------|:---------|
| `FilterNotValidException` | 1045 | Formato de filtro incorrecto |
| `FilterNotValidException` (validación) | 1059 | Combinación operador-tipo inválida (IN+DATE, LIKE+DATE) |
| `FilterOperatorNotValidException` | 1046 | Operador no existe en enum |
| `OrderByNotValidException` | 1043 | Formato de orden incorrecto |
| `OrderTypeNotValidException` | 1044 | Tipo de orden no existe en enum |
| `FieldNotValidException` | 1048 | Campo vacío |
| `ValueTypeNotValidException` | 1057 | Tipo de valor no existe en enum |

#### Propagación de Errores
- Las excepciones se lanzan desde ValueObjects y servicios de dominio.
- El `CriteriaService` **no captura** excepciones, las propaga al controlador.
- Los controladores deben usar `ErrorHandler` para transformar excepciones en respuestas HTTP estandarizadas.

### Integración con Sistemas de Monitorización
- **Logs:** PSR-3 → Log4Php → Grafana Alloy → Loki.
- **Métricas potenciales:**
  - Contador de criterios parseados (`criteria_parsed_total`).
  - Contador de errores por tipo (`criteria_errors_total{type="invalid_operator"}`).
  - Histograma de número de filtros por request (`criteria_filters_count`).
  - Latencia de conversión a Doctrine (`criteria_conversion_duration_seconds`).
- **Alertas:**
  - Spike en `FilterOperatorNotValidException` (posible intento de ataque).
  - Tasa de error > 5% en parsing de criterios.

---

## 11. Testing

### Estrategia de Testing
- **Unit Tests:** Servicios con mock de logger, ValueObjects con datos de prueba.
- **Integration Tests:** `DoctrineCriteriaConverter` contra objetos `Criteria` reales, verificar SQL generado.
- **Validation Tests:** Verificar que cada ValueObject valida correctamente y lanza excepciones esperadas.
- **Edge Cases:** Filtros vacíos, valores límite (offset/limit negativos), combinaciones inválidas.

### Tipos de Pruebas Aplicables

#### Unit Tests (ValueObjects)
```php
// Test: Operator válido
$operator = new Operator('eq');
$this->assertEquals('=', $operator->value());

// Test: Operator inválido lanza excepción
$this->expectException(FilterOperatorNotValidException::class);
new Operator('invalid');

// Test: OrderType válido
$orderType = new OrderType('asc');
$this->assertEquals('ASC', $orderType->value());

// Test: Limit negativo se convierte a 0
$limit = new Limit(-10);
$this->assertEquals(0, $limit->value());
```

#### Unit Tests (Servicios)
```php
// Test: GetFiltersService parsea filtros correctamente
$requestData = ['filter' => 'status:eq:active,email:lk:john'];
$filters = $service->get($requestData);

$this->assertCount(2, $filters);
$this->assertEquals('status', $filters[0]->field());
$this->assertEquals('=', $filters[0]->operator());
$this->assertEquals('active', $filters[0]->value());

// Test: GetFiltersService retorna array vacío sin parámetro filter
$filters = $service->get([]);
$this->assertEmpty($filters);

// Test: GetFiltersService lanza excepción con formato inválido
$this->expectException(FilterNotValidException::class);
$service->get(['filter' => 'invalid']);
```

#### Integration Tests (DoctrineCriteriaConverter)
```php
// Test: Conversión completa de Criteria a Doctrine
$criteria = new Criteria(
    [new Filter('status', 'eq', 'active', 'string')],
    [new Order('createdAt', 'desc')],
    new Pagination(20, 0)
);

$doctrineCriteria = $converter->get($criteria);

$this->assertInstanceOf(CollectionsCriteria::class, $doctrineCriteria);
$this->assertNotNull($doctrineCriteria->getWhereExpression());
$this->assertEquals(['createdAt' => 'DESC'], $doctrineCriteria->getOrderings());
$this->assertEquals(20, $doctrineCriteria->getMaxResults());
$this->assertEquals(0, $doctrineCriteria->getFirstResult());
```

### Ejemplos de Casos de Prueba Relevantes

#### Caso 1: Filtro simple con operador eq
```php
$requestData = ['filter' => 'status:eq:active'];
$criteria = $criteriaService->getCriteria($requestData);

$this->assertCount(1, $criteria->filters());
$this->assertEquals('status', $criteria->filters()[0]->field());
$this->assertEquals('=', $criteria->filters()[0]->operator());
$this->assertEquals('active', $criteria->filters()[0]->value());
```

#### Caso 2: Filtro con fecha
```php
$requestData = ['filter' => 'createdAt:ge:2025-01-01:date'];
$criteria = $criteriaService->getCriteria($requestData);

$filter = $criteria->filters()[0];
$this->assertInstanceOf(DateTime::class, $filter->value());
$this->assertEquals('2025-01-01', $filter->value()->format('Y-m-d'));
```

#### Caso 3: Operador IN con múltiples valores
```php
$requestData = ['filter' => 'id:in:1|2|3'];
$criteria = $criteriaService->getCriteria($requestData);

$doctrineCriteria = $converter->get($criteria);
// Verificar que Doctrine recibe array [1, 2, 3]
```

#### Caso 4: Validación cruzada IN + DATE lanza excepción
```php
$this->expectException(FilterNotValidException::class);
$this->expectExceptionMessage('The IN operator cannot be used with DATETIME values');
new Filter('createdAt', 'in', '2025-01-01|2025-12-31', 'date');
```

#### Caso 5: Múltiples filtros y ordenaciones
```php
$requestData = [
    'filter' => 'status:eq:active,age:gt:18',
    'order_by' => 'createdAt:DESC,name:ASC',
    'limit' => '50',
    'offset' => '10'
];

$criteria = $criteriaService->getCriteria($requestData);

$this->assertCount(2, $criteria->filters());
$this->assertCount(2, $criteria->orders());
$this->assertEquals(50, $criteria->pagination()->limit());
$this->assertEquals(10, $criteria->pagination()->offset());
```

#### Caso 6: Normalización de keys a lowercase
```php
$requestData = [
    'FILTER' => 'status:eq:active',
    'Order_By' => 'name:ASC',
    'LIMIT' => '20'
];

$criteria = $criteriaService->getCriteria($requestData);
// Debe funcionar correctamente a pesar de mayúsculas
$this->assertCount(1, $criteria->filters());
$this->assertCount(1, $criteria->orders());
```

---

## 12. Limitaciones Conocidas

### Restricciones Actuales
1. **Solo AND entre filtros:** No se soportan condiciones OR, agrupaciones con paréntesis o lógica compleja.
   
2. **Operadores limitados:** Solo 8 operadores básicos; no se soportan operadores personalizados o complejos (ej. `BETWEEN`, `IS NULL`, `REGEXP`).

3. **Valores escalares únicamente:** No se pueden filtrar por arrays complejos, objetos JSON o relaciones anidadas.

4. **Sin soporte para subconsultas:** No se pueden construir filtros basados en resultados de otras queries.

5. **Conversión automática solo para DateTime:** Solo se soportan tipos `string` y `date`; no hay soporte para `int`, `float`, `bool`, `array`.

6. **No valida existencia de campos:** El módulo no conoce el schema de BD, por lo que no puede validar si un campo existe en la entidad. Esto genera errores en tiempo de ejecución de Doctrine.

7. **Operador LIKE sin wildcards configurables:** El operador `lk` se mapea a `CONTAINS` (equivalente a `%valor%`), no permite wildcards personalizados como `STARTS_WITH` o `ENDS_WITH`.

8. **Offset-based pagination únicamente:** No soporta cursor-based pagination, que es más eficiente para datasets grandes.

9. **Sin soporte para agregaciones:** No se pueden construir criterios para `COUNT`, `SUM`, `AVG`, etc.

10. **Límite máximo global:** `maxResults` es global para toda la aplicación; no se puede configurar por entidad/endpoint.

### Escenarios No Cubiertos
- Búsquedas con lógica OR (ej. `(status=active OR status=pending) AND createdAt > 2025-01-01`).
- Filtros en relaciones anidadas (ej. `user.role.name:eq:admin`).
- Búsquedas full-text o fuzzy matching.
- Criterios basados en cálculos (ej. `age > DATEDIFF(NOW(), birthDate)`).
- Filtros con NULL (ej. `email IS NULL`).
- Geo-queries (ej. distancia, within polygon).

### Deuda Técnica Identificable
- **Validación de campos:** Debería existir un mecanismo para registrar campos permitidos por entidad y validarlos en servicios de dominio.
- **Conversión de tipos extensible:** El sistema de tipos (`ValuesType`) debería ser extensible vía estrategia/plugin.
- **Logging configurable:** El nivel de logging está hardcodeado; debería ser configurable por entorno.
- **Sin tests de performance:** No hay tests que validen comportamiento bajo alta carga o con criterios complejos.
- **Dependencia de Doctrine en dominio implícita:** Aunque el dominio no importa Doctrine, el diseño asume que se usará Doctrine, dificultando migración a otro ORM.

---

## 13. Posibles Mejoras Futuras

### Refactors Sugeridos
1. **Registro de campos permitidos:** Crear un `FieldRegistry` que mapee entidades a campos consultables y los valide en `GetFiltersService`.

2. **Builder fluido para Criteria:** Implementar un builder con API fluida:
   ```php
   $criteria = CriteriaBuilder::create()
       ->filter('status', Operators::EQUAL, 'active')
       ->filter('age', Operators::GREATER, 18)
       ->orderBy('createdAt', OrdersType::DESC)
       ->paginate(limit: 20, offset: 0)
       ->build();
   ```

3. **ValueType extensible:** Permitir registro de tipos personalizados (ej. `uuid`, `email`, `url`) con validadores propios.

4. **Logging condicional:** Añadir parámetro de configuración `debug_criteria_logging` para controlar verbosidad.

### Funcionalidades Potenciales
1. **Soporte para operador OR:**
   - Sintaxis propuesta: `?filter=(status:eq:active|status:eq:pending),age:gt:18`
   - Requiere parser de expresiones más complejo.

2. **Operador IS NULL:**
   - Sintaxis: `?filter=email:null`
   - Mapea a `IS NULL` en SQL.

3. **Wildcards configurables para LIKE:**
   - `email:sw:john` → `LIKE 'john%'` (starts with)
   - `email:ew:.com` → `LIKE '%.com'` (ends with)

4. **Cursor-based pagination:**
   - `?cursor=eyJpZCI6MTIzfQ==&limit=20` (cursor codifica último ID visto)
   - Más eficiente que offset para datasets grandes.

5. **Filtros en relaciones:**
   - Sintaxis: `?filter=user.role.name:eq:admin`
   - Requiere introspección de metadata de Doctrine.

6. **Búsqueda full-text:**
   - `?search=john+doe` (busca en campos indexados full-text)
   - Integración con Elasticsearch o MySQL FULLTEXT.

7. **Soporte para agregaciones:**
   - `?aggregate=count&group_by=status`
   - Retornar JSON con resultados agregados.

8. **Validación de campos contra schema:**
   - Integración con metadata de Doctrine para validar que campos existen.
   - Retornar error 400 con lista de campos permitidos si inválido.

9. **Rate limiting por complejidad:**
   - Asignar "costo" a cada operador/filtro.
   - Limitar requests que excedan umbral de complejidad.

10. **GraphQL-style field selection:**
    - `?fields=id,name,email` (proyección de campos)
    - Optimizar queries para traer solo campos solicitados.

### Mejoras de Arquitectura
1. **Abstracción de ORM:** Crear interfaz `CriteriaConverter` con implementaciones para diferentes ORMs (Doctrine, Eloquent, Propel).

2. **Plugin system para operadores:** Permitir registro de operadores personalizados vía plugin.

3. **Query caching automático:** Cachear resultados de queries basadas en criterios con hash de parámetros.

4. **Event sourcing de queries:** Emitir eventos de dominio `CriteriaQueried` para analytics/auditoría.

5. **CQRS con read models:** Generar vistas materializadas optimizadas para búsquedas frecuentes.

6. **API Gateway integration:** Centralizar parsing de criterios en API Gateway, pasar objetos serializados a microservicios.

7. **OpenAPI schema generation:** Generar automáticamente schema OpenAPI con filtros/operadores permitidos por endpoint.
