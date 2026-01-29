---
name: php-expert
description: |
  Expert PHP development with modern PHP 8.3+ features, frameworks, and best practices. Desarrollo experto en PHP con características modernas de PHP 8.3+, frameworks y mejores prácticas.

  **Use for ANY PHP task / Usar para CUALQUIER tarea de PHP:** writing PHP code (escribir código PHP), building APIs (construir APIs), developing web applications (desarrollar aplicaciones web), working with frameworks (trabajar con frameworks: Laravel, Symfony), debugging PHP issues (depurar problemas de PHP), refactoring PHP code (refactorizar código PHP), reviewing PHP code (revisar código PHP), optimizing performance (optimizar rendimiento), implementing security (implementar seguridad), or answering PHP architecture questions (responder preguntas de arquitectura PHP).

  **PHP Versions / Versiones PHP:** PHP 8.3, PHP 8.4 (latest features, características últimas), PHP 8.0+ (modern features, características modernas).

  **Core PHP / PHP Core:** OOP (programación orientada a objetos), types & typing (tipos y tipado), namespaces (espacios de nombres), autoloading (carga automática), exceptions (excepciones), error handling (manejo de errores), file handling (manejo de archivos), sessions (sesiones), cookies.

  **Modern PHP Features / Características Modernas PHP:** union types (tipos unión), readonly properties (propiedades de solo lectura), enum (enumeraciones), match expression (expresión match), constructor property promotion (promoción de propiedades del constructor), named arguments (argumentos nombrados), attributes (atributos), fibers (fibers), JIT compilation (compilación JIT).

  **Frameworks / Frameworks:** Laravel (Eloquent ORM, Blade, Eloquent relationships, Artisan, migrations), Symfony (components, Doctrine ORM, Twig, Console, Event Dispatcher), Slim (microframework), Laminas (formerly Zend Framework), CodeIgniter.

  **Testing / Pruebas:** PHPUnit, Pest, testing patterns (patrones de testing), TDD (desarrollo guiado por pruebas), test doubles (dobles de prueba: mocks, stubs).

  **Databases / Bases de Datos:** MySQL, PostgreSQL, SQLite, PDO (PHP Data Objects), MySQLi, query builders (constructores de consultas), ORM (Object-Relational Mapping), Eloquent, Doctrine.

  **API Development / Desarrollo de APIs:** REST APIs, JSON APIs, API authentication (autenticación de API: JWT, OAuth, API keys), API versioning (versionado de API), API documentation (documentación de API: OpenAPI/Swagger).

  **Security / Seguridad:** OWASP Top 10 for PHP, input validation (validación de entrada), output escaping (escapado de salida), SQL injection prevention (prevención de inyección SQL), XSS prevention (prevención de XSS), CSRF protection (protección CSRF), password hashing (hashing de contraseñas: password_hash, bcrypt, Argon2), authentication (autenticación), authorization (autorización).

  **Performance / Rendimiento:** OPcache, profiling (perfilado), caching strategies (estrategias de caché: Redis, Memcached), database optimization (optimización de base de datos), lazy loading (carga diferida), connection pooling (agrupación de conexiones).

  **Package Management / Gestión de Paquetes:** Composer (dependency manager, gestor de dependencias), packagist.org, package versioning (versionado de paquetes), semantic versioning (versionado semántico).

  **Tooling / Herramientas:** PHPStan (static analysis, análisis estático), Psalm (type checking, verificación de tipos), Rector (automated refactoring, refactorización automática), PHP CS Fixer (code style, estilo de código), PHP Mess Detector (PHPMD).

  **Deployment / Despliegue:** Docker, containerization (contenedorización), CI/CD pipelines, server configuration (configuración de servidor: Apache, Nginx), PHP-FPM, environment configuration (configuración de entorno).

  **Applies / Aplica:** Clean Code principles (principios de código limpio), SOLID design (diseño SOLID), DRY (Don't Repeat Yourself), design patterns (patrones de diseño), PSR standards (estándares PSR: PSR-4 autoloading, PSR-12 code style), documentation-first workflow (flujo de trabajo documentación primero).
---

# PHP Expert

## Modern PHP Development (PHP 8.3+)

### PHP 8.3+ Features

```php
// Readonly properties (PHP 8.1+)
readonly class UserData
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// Enums (PHP 8.1+)
enum Status: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
}

// Match expression (PHP 8.0+)
$result = match ($statusCode) {
    200, 300 => 'Success',
    400, 404 => 'Client error',
    500 => 'Server error',
    default => 'Unknown',
};

// Constructor property promotion (PHP 8.0+)
class User
{
    public function __construct(
        public string $name,
        public string $email,
        private readonly string $id,
    ) {}

    // Named arguments (PHP 8.0+)
    public function update(array $data): void
    {
        $this->name = $data['name'] ?? $this->name;
    }
}

// Attributes (PHP 8.0+)
#[Route('/api/users', methods: ['GET'])]
public function getUsers(): Response
{
    // ...
}

// Fibers for concurrent code (PHP 8.1+)
$fiber = new Fiber(function (): void {
    $value = Fiber::suspend('suspended');
    echo "Resumed with: $value";
});

echo $fiber->start();  // Output: suspended
$fiber->resume('hello');  // Output: Resumed with: hello
```

### Type System

```php
// Union types (PHP 8.0+)
function process(int|float|string $value): void
{
    // ...
}

// Intersection types (PHP 8.1+)
function countable(iterable&Countable $items): int
{
    return count($items);
}

// Strict types declaration
declare(strict_types=1);

class UserService
{
    // Return types and parameter types
    public function getUserById(int $id): ?User
    {
        return User::find($id);
    }

    // Never return type (PHP 8.1+)
    public function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    // Mixed type
    public function processInput(mixed $input): string
    {
        return is_string($input) ? $input : json_encode($input);
    }
}
```

## Clean Code Principles

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `UserRepository`, `EmailService` |
| Methods | camelCase | `getUserById()`, `sendEmail()` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRIES`, `API_BASE_URL` |
| Variables | camelCase | `$userName`, `$totalAmount` |
| Private properties | camelCase | `private $cachedData` |

### Function and Class Design

```php
// ✅ Good: Single responsibility, clear name
class EmailValidator
{
    public function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// ❌ Bad: Vague name, multiple responsibilities
class Helper
{
    public function process($data)
    {
        // Does validation, sending, and logging all in one
    }
}

// ✅ Good: Early returns, guard clauses
function processOrder(Order $order): ActionResult
{
    if ($order->isEmpty()) {
        return ActionResult::failure('Order is empty');
    }

    if (!$order->isValid()) {
        return ActionResult::failure('Invalid order data');
    }

    return $this->saveOrder($order);
}

// ❌ Bad: Deep nesting
function processOrder(Order $order): ActionResult
{
    if (!$order->isEmpty()) {
        if ($order->isValid()) {
            return $this->saveOrder($order);
        } else {
            return ActionResult::failure('Invalid');
        }
    } else {
        return ActionResult::failure('Empty');
    }
}
```

## SOLID Principles in PHP

### Single Responsibility Principle

```php
// ❌ Bad: User model handles database, email, and logging
class User
{
    public function save(): bool { /* ... */ }
    public function sendWelcomeEmail(): void { /* ... */ }
    public function logActivity(): void { /* ... */ }
}

// ✅ Good: Separated concerns
class User
{
    public function __construct(
        private UserRepository $repository,
        private EmailService $emailService,
        private ActivityLogger $logger,
    ) {}

    public function save(): bool
    {
        return $this->repository->save($this);
    }

    public function sendWelcomeEmail(): void
    {
        $this->emailService->sendWelcome($this);
    }
}
```

### Dependency Inversion Principle

```php
// ✅ Depend on abstractions (interfaces)
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): bool;
}

class UserService
{
    public function __construct(
        private CacheInterface $cache,  // Can inject Redis, Memcached, etc.
    ) {}

    public function getUser(int $id): ?User
    {
        $cached = $this->cache->get("user:{$id}");
        if ($cached !== null) {
            return $cached;
        }

        $user = $this->repository->find($id);
        if ($user) {
            $this->cache->set("user:{$id}", $user, 3600);
        }

        return $user;
    }
}
```

## Error Handling

### Exceptions

```php
// Custom exception hierarchy
abstract class DomainException extends \Exception {}

class UserNotFoundException extends DomainException {}
class ValidationException extends DomainException {}

// Try-catch with specific exceptions
try {
    $user = $this->userService->getUser($id);
} catch (UserNotFoundException $e) {
    return new JsonResponse(['error' => 'User not found'], 404);
} catch (ValidationException $e) {
    return new JsonResponse(['error' => $e->getMessage()], 422);
} catch (\Throwable $e) {
    $this->logger->error('Unexpected error', ['exception' => $e]);
    return new JsonResponse(['error' => 'Internal error'], 500);
}

// Finally for cleanup
try {
    $file = fopen('/tmp/data.txt', 'r');
    // Process file
} finally {
    if (isset($file)) {
        fclose($file);
    }
}
```

### Best Practices

- **Always type hint** - Use parameter types and return types
- **Never catch `Exception`** - Catch specific exceptions
- **Log unexpected errors** - Use monolog/psr/log
- **Convert errors to exceptions** - `set_error_handler()`

```php
// Convert errors to exceptions
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
```

## Design Patterns

### Repository Pattern

```php
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): bool;
    public function delete(int $id): bool;
}

class DatabaseUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private PDO $connection,
    ) {}

    public function findById(int $id): ?User
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }
}
```

### Factory Pattern

```php
interface PaymentProcessorFactoryInterface
{
    public function create(string $type): PaymentProcessorInterface;
}

class PaymentProcessorFactory implements PaymentProcessorFactoryInterface
{
    public function create(string $type): PaymentProcessorInterface
    {
        return match ($type) {
            'stripe' => new StripeProcessor(),
            'paypal' => new PayPalProcessor(),
            'braintree' => new BraintreeProcessor(),
            default => throw new InvalidArgumentException("Unknown processor: {$type}"),
        };
    }
}
```

### Service Layer

```php
class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private PaymentService $paymentService,
        private EmailService $emailService,
    ) {}

    public function createOrder(int $userId, array $items): Order
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        $order = new Order($user, $items);
        $this->validateOrder($order);

        $this->orderRepository->save($order);

        // Process payment asynchronously
        $this->paymentService->process($order);

        // Send confirmation email
        $this->emailService->sendOrderConfirmation($user, $order);

        return $order;
    }

    private function validateOrder(Order $order): void
    {
        if ($order->isEmpty()) {
            throw new ValidationException('Order cannot be empty');
        }

        if ($order->getTotal() <= 0) {
            throw new ValidationException('Invalid total amount');
        }
    }
}
```

## Framework Selection

### Laravel

**Best for:**
- Rapid application development
- Full-stack web applications
- Teams new to PHP frameworks
- Projects requiring extensive ecosystem

**Key features:** Eloquent ORM, Blade templates, Artisan CLI, migrations, queues

### Symfony

**Best for:**
- Large-scale enterprise applications
- Projects requiring flexibility
- Reusable component library
- Microservices architecture

**Key features:** Components, Doctrine ORM, Twig, Console, Event Dispatcher

### Slim

**Best for:**
- Simple APIs and microservices
- Minimal overhead
- Learning purposes

## Code Organization

### PSR-4 Autoloading

```
src/
├── Controller/
│   ├── UserController.php
│   └── OrderController.php
├── Service/
│   ├── UserService.php
│   └── OrderService.php
├── Repository/
│   ├── UserRepository.php
│   └── OrderRepository.php
├── Model/
│   ├── User.php
│   └── Order.php
└── Exception/
    ├── UserNotFoundException.php
    └── ValidationException.php
```

```json
// composer.json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

### Configuration

```php
// Environment-based configuration
$env = getenv('APP_ENV') ?: 'production';

return match ($env) {
    'development' => [
        'debug' => true,
        'db' => [
            'host' => 'localhost',
            'name' => 'app_dev',
        ],
    ],
    'production' => [
        'debug' => false,
        'db' => [
            'host' => getenv('DB_HOST'),
            'name' => getenv('DB_NAME'),
        ],
    ],
};
```

## Performance Optimization

### OPcache Configuration

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
```

### Caching Strategies

```php
// Redis caching
class CacheService
{
    public function __construct(
        private Redis $redis,
    ) {}

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $cached = $this->redis->get($key);
        if ($cached !== null) {
            return unserialize($cached);
        }

        $result = $callback();
        $this->redis->setex($key, $ttl, serialize($result));

        return $result;
    }
}
```

### Database Optimization

```php
// Use prepared statements
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// Batch inserts
$stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
foreach ($users as $user) {
    $stmt->execute([
        'name' => $user['name'],
        'email' => $user['email'],
    ]);
}

// Use connection pooling (doctrine/route)
```

## Security Best Practices

### Input Validation

```php
// Validate and sanitize input
class UserRegistrationData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (strlen($this->name) < 2) {
            throw new ValidationException('Name too short');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email');
        }

        if (strlen($this->password) < 8) {
            throw new ValidationException('Password too short');
        }
    }
}
```

### Output Escaping

```php
// Always escape output
echo htmlspecialchars($userInput, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Use context-aware escaping (Twig, Blade)
// {{ variable }} - auto-escaped
// {!! variable !!} - raw (dangerous)
```

### Password Hashing

```php
// Use current best practices
$password = 'user-password';

// Hash password (uses bcrypt by default)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify password
if (password_verify($inputPassword, $hash)) {
    // Check if password needs rehash
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
        // Update hash in database
    }
}
```

## Testing

This section covers PHPUnit testing patterns for PHP applications.

### PHPUnit Example

```php
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $service;
    private UserRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->service = new UserService($this->repository);
    }

    public function testGetUserByIdReturnsUser(): void
    {
        $expectedUser = new User('John Doe', 'john@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($expectedUser);

        $actualUser = $this->service->getUserById(1);

        $this->assertSame($expectedUser, $actualUser);
    }

    public function testGetUserByIdThrowsWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->service->getUserById(999);
    }
}
```

## Quality Tools

### Static Analysis

```bash
# PHPStan - Level 8 is strictest
vendor/bin/phpstan analyse src --level=8

# Psalm
vendor/bin/psalm --show-info=true
```

### Code Style

```bash
# PHP CS Fixer
vendor/bin/php-cs-fixer fix

# PHP Mess Detector
vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode
```

### Automated Refactoring

```bash
# Rector - Upgrade to PHP 8.3
vendor/bin/rector process src --set php83

# Add return types
vendor/bin/rector process src --set solid-to-readonly
```

## Project Structure

```
project/
├── config/             # Configuration files
├── public/             # Document root (index.php)
├── src/
│   ├── Controller/     # HTTP controllers
│   ├── Service/        # Business logic
│   ├── Repository/     # Data access
│   ├── Model/          # Domain models
│   ├── Exception/      # Custom exceptions
│   └── Helper/         # Utility classes
├── tests/
│   ├── Unit/           # Unit tests
│   └── Integration/    # Integration tests
├── vendor/             # Composer dependencies
├── .env                # Environment config
├── .env.example        # Example env config
├── composer.json       # Dependencies
├── phpunit.xml         # PHPUnit config
├── phpstan.neon        # PHPStan config
└── rector.php          # Rector config
```

## Code Review Checklist

Before submitting:

- [ ] PHP 8.3+ features used appropriately
- [ ] Strict types enabled (`declare(strict_types=1)`)
- [ ] All methods have return types
- [ ] All parameters have types
- [ ] No `mixed` types without justification
- [ ] PSR-12 code style followed
- [ ] No code smells (PHPMD)
- [ ] Static analysis passes (PHPStan/Psalm level 8)
- [ ] Tests cover new functionality
- [ ] No `var_dump()` or `print_r()` debugging
- [ ] Error handling implemented
- [ ] Security best practices followed
- [ ] Documentation added (PHPDoc)
