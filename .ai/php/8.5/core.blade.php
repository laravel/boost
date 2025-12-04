## PHP 8.5

- PHP 8.5 introduces new array functions for cleaner code:
    - `array_first(array $array): mixed` - Get first value (or `null` if empty)
    - `array_last(array $array): mixed` - Get last value (or `null` if empty)

- The `#[\NoDiscard]` attribute warns when a function's return value is ignored. Use `(void)` cast to suppress intentionally.

### Pipe Operator
- The pipe operator (`|>`) chains function calls left-to-right, replacing nested calls:
<code-snippet name="Pipe Operator Example" lang="php">
// Before PHP 8.5
$slug = strtolower(str_replace(' ', '-', trim($title)));

// After PHP 8.5
$slug = $title |> trim(...) |> (fn($s) => str_replace(' ', '-', $s)) |> strtolower(...);
</code-snippet>

### Clone With
- Properties can be modified during cloning using `clone($object, ['property' => $value])`:
<code-snippet name="Clone With Example" lang="php">
// Before PHP 8.5 (readonly classes required manual reconstruction)
$new = new User(...get_object_vars($user), role: 'admin');

// After PHP 8.5
$new = clone($user, ['role' => 'admin']);
</code-snippet>
