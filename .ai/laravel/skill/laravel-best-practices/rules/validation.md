# Validation and Forms Best Practices

## Extract Validation When It Improves the Boundary

Use a form request when validation or authorization is substantial, reused, or clearer outside the controller; inline `$request->validate()` suits small endpoint-specific rules. Validation checks the shape and values of input; use the form request's `authorize()` or a policy for access.

## Prefer Readable Rule Syntax

Array syntax composes with rule objects and avoids delimiter issues (`['required', 'email', Rule::unique('users')]`); prefer it in new code. String syntax (`'required|email'`) is fine for simple rules. Follow the local style.

## Use Only Intended Validated Data

Pass `validated()` or `safe()` onward, never `$request->all()`. Use `$request->safe()->only(['title', 'body'])` when rules also cover control fields or nested data. Validated data is not automatically safe for mass assignment: keep `$fillable`/`$guarded` aligned, and never add a sensitive attribute to the rules just to make mass assignment convenient.

## Express Conditional Rules Clearly

Use `Rule::when()`, `required_if`, or `exclude_unless`, whichever is simplest to read and test.

## Cross-Field Validation in `after()`

Use a form request's `after()` for rules depending on multiple fields or application state. Return early when `$validator->errors()->hasAny([...])` shows prerequisite fields failed, to avoid needless queries.

Validating against mutable state does not prevent races between validation and persistence. Enforce stock, uniqueness, and similar invariants with database constraints, atomic updates, or transactions.
