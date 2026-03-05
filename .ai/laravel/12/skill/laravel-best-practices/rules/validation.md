---
title: Validation & Forms Best Practices
impact: HIGH
tags: validation, form-request, rules
---

# Validation & Forms Best Practices

## Use Form Request Classes

Extract validation from controllers into dedicated Form Request classes.

Incorrect:
```php
public function store(Request $request)
{
    $request->validate([
        'title' => 'required|max:255',
        'body' => 'required',
    ]);
}
```

Correct:
```php
public function store(StorePostRequest $request)
{
    Post::create($request->validated());
}
```

## Use Array Notation for Rules

Array syntax is more readable and works with Rule objects.

Incorrect:
```php
'email' => 'required|email|unique:users',
```

Correct:
```php
'email' => ['required', 'email', Rule::unique('users')],
```

## Always Use `validated()`

Get only validated data. Never use `$request->all()` for mass operations.

Incorrect:
```php
Post::create($request->all());
```

Correct:
```php
Post::create($request->validated());
```

## Create Invokable Rule Classes

For complex reusable validation logic, create dedicated Rule classes.

```php
class Uppercase implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strtoupper($value) !== $value) {
            $fail('The :attribute must be uppercase.');
        }
    }
}

// Usage
'code' => ['required', 'string', new Uppercase],
```

## Use `Rule::when()` for Conditional Validation

```php
'company_name' => [
    Rule::when($this->account_type === 'business', ['required', 'string', 'max:255']),
],
```

## Use the `after()` Method for Custom Validation

Use `after()` instead of `withValidator()` for custom validation logic that depends on multiple fields.

```php
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($this->quantity > Product::find($this->product_id)?->stock) {
                $validator->errors()->add('quantity', 'Not enough stock.');
            }
        },
    ];
}
```
