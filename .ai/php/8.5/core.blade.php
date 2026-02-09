# PHP 8.5

PHP 8.5 introduces new array functions that simplify code when not using Laravel collections:

- `array_first(array $array): mixed` - Get first value (or `null` if empty).
- `array_last(array $array): mixed` - Get last value (or `null` if empty).

## Pipe Operator

The pipe operator (`|>`) chains function calls left-to-right, replacing nested calls:

<!-- Pipe Operator Example -->
```php
// Before PHP 8.5
$slug = strtolower(str_replace(' ', '-', trim($title)));

// After PHP 8.5
$slug = $title |> trim(...) |> (fn($s) => str_replace(' ', '-', $s)) |> strtolower(...);
```

## Cloning

You may use `clone($object, ['property' => $value])` to modify properties during cloning; this is ideal for readonly classes.
