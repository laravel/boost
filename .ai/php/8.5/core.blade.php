## PHP 8.5

- PHP 8.5 has new array functions that will make code simpler whenever we don't use Laravel's collections:
    - `array_first(array $array): mixed` - Get first value (or `null` if empty).
    - `array_last(array $array): mixed` - Get last value (or `null` if empty).

- Use `clone($object, ['property' => $value])` to modify properties during cloning; this is ideal for readonly classes.

### Pipe Operator
- The pipe operator (`|>`) chains function calls left-to-right, replacing nested calls:
<code-snippet name="Pipe Operator Example" lang="php">
// Before PHP 8.5
$slug = strtolower(str_replace(' ', '-', trim($title)));

// After PHP 8.5
$slug = $title |> trim(...) |> (fn($s) => str_replace(' ', '-', $s)) |> strtolower(...);
</code-snippet>
