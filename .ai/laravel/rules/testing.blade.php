---
paths:
  - "tests/**"
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `{{ $assist->artisanCommand('make:test [options] {name}') }}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.
