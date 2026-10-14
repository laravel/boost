# Convention and Style Best Practices

## Follow Project Naming Conventions

Use Laravel's conventions in new code, but keep an established project convention unless a coordinated rename is worthwhile.

| Element | Convention | Example |
| --- | --- | --- |
| Controller | Singular resource name | `ArticleController` |
| Model | Singular StudlyCase | `User` |
| Table | Plural snake_case | `article_comments` |
| Pivot table | Singular model names, alphabetical, snake_case | `article_user` |
| Column | snake_case | `meta_title` |
| Foreign key | Singular model name plus `_id` | `article_id` |
| Resource URI | Plural resource | `articles/1` |
| Route name | Dotted segments | `users.show_active` |
| Method / variable | camelCase | `getAll`, `$articlesWithAuthor` |
| Collection / object | Descriptive plural / singular | `$activeUsers`, `$activeUser` |
| View | kebab-case | `show-filtered.blade.php` |
| Configuration file | snake_case | `google_calendar.php` |
| Enumeration | Singular StudlyCase | `UserType` |

## Prefer Clear, Idiomatic Syntax

Use helpers and query methods when they state intent more directly, but do not shorten code when it becomes ambiguous or loses type information.

- `session('cart')` over `Session::get('cart')` or `$request->session()->get('cart')`
- `back()` over `Redirect::back()`, `now()` over `Carbon::now()`
- `latest()` / `oldest()` over `orderBy('created_at', 'desc'|'asc')`
- `->value('name')` over `->first()?->name` when only that column is needed
- `$request->string()`, `integer()`, `boolean()` when their coercion matches the operation

## Use Utilities When They Clarify Intent

`Str`, `Arr`, `Number`, and `Uri` are worth using when clearer or safer than the raw PHP equivalent, not as a blanket replacement. `Number` is for display formatting, never for values you store or calculate with. `Uri::of()` helps when building or transforming URLs with query strings. Check the documentation for the project's Laravel version before using newer utilities.

## Keep Presentation Code Maintainable

Put substantial JavaScript and CSS in the project's asset pipeline or components; small page-specific scripts in layouts or stacks are fine. Pass server data to JavaScript with `Js::from($article)`, which encodes safely. Data attributes suit small scalars; serializing a whole model into one can expose unnecessary fields and complicate escaping.

## Write Comments That Explain Why

Prefer clear names and small methods (`$this->hasJoins()`) over comments restating the code. Comment only non-obvious constraints, tradeoffs, workarounds, regexes, or external behavior, and keep them accurate.
