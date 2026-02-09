## PHP 8.4

PHP 8.4 introduces new array functions that simplify code when not using Laravel collections:

- `array_find(array $array, callable $callback): mixed` - Find first matching element.
- `array_find_key(array $array, callable $callback): int|string|null` - Find first matching key.
- `array_any(array $array, callable $callback): bool` - Check if any element satisfies a callback function.
- `array_all(array $array, callable $callback): bool` - Check if all elements satisfy a callback function.

## Cleaner Chaining on New Instances

No extra parentheses are needed when chaining on new object instances:

<!-- New Object Chaining Example -->
```php
// Before PHP 8.4
$response = (new JsonResponse(['data' => $data]))->setStatusCode(201);

// After PHP 8.4
$response = new JsonResponse(['data' => $data])->setStatusCode(201);
```
