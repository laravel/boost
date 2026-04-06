# PHP

@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
@if(empty($assist->enums()) || !preg_match('/[A-Z]{3,8}/', $assist->enumContents()))
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
@else
- Follow existing application Enum naming conventions.
@endif
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.
