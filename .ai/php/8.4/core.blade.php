## PHP 8.4

Use these array functions instead of manual loops when not using Laravel collections:
- `array_find(array $array, callable $callback): mixed` - first matching element
- `array_find_key(array $array, callable $callback): int|string|null` - first matching key
- `array_any(array $array, callable $callback): bool` - true if any element matches
- `array_all(array $array, callable $callback): bool` - true if all elements match

Chain directly on new instances without wrapping in parentheses:
```php
// Before: $response = (new JsonResponse(['data' => $data]))->setStatusCode(201);
$response = new JsonResponse(['data' => $data])->setStatusCode(201);
```
