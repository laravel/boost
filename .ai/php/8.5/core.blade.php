## PHP 8.5

Use these array functions instead of manual loops when not using Laravel collections:
- `array_first(array $array): mixed` - first value or `null` if empty
- `array_last(array $array): mixed` - last value or `null` if empty

Use the pipe operator (`|>`) to chain function calls left-to-right instead of nesting:
```php
// Before: $slug = strtolower(str_replace(' ', '-', trim($title)));
$slug = $title |> trim(...) |> (fn($s) => str_replace(' ', '-', $s)) |> strtolower(...);
```

Use `clone($object, ['property' => $value])` to modify properties during cloning. Ideal for readonly classes.
