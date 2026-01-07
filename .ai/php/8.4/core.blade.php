## PHP 8.4

- PHP 8.4 has new array functions that will make code simpler whenever we don't use Laravel's collections:
    - `array_find(array $array, callable $callback): mixed` - Find first matching element.
    - `array_find_key(array $array, callable $callback): int|string|null` - Find first matching key.
    - `array_any(array $array, callable $callback): bool` - Check if any element satisfies a callback function.
    - `array_all(array $array, callable $callback): bool` - Check if all elements satisfy a callback function.

### Cleaner Chaining on New Instances
- No extra parentheses are needed when chaining on new object instances:

<code-snippet name="New Object Chaining Example" lang="php">
// Before PHP 8.4
$response = (new JsonResponse(['data' => $data]))->setStatusCode(201);

// After PHP 8.4
$response = new JsonResponse(['data' => $data])->setStatusCode(201);
</code-snippet>
