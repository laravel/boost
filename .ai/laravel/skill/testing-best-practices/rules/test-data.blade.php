@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Factories and Test Data

## Each Test Makes Its Own Data

@if($pest)
Create mutable records inside the test, so setup is visible and each test picks its factory state. Use `beforeEach()` only for configuration, never records.
@else
Create mutable records inside the test or a private helper it calls. Use `setUp()` only for configuration, never records, since they stay in memory until the suite ends.
@endif

## Record Construction

- `create()` when the database is needed; `make()` only when it is not (rendering a notification, a value object).
- Prefer a named state over raw attributes: `User::factory()->unverified()->create()` carries meaning, `create(['email_verified_at' => null])` only a value.
- `for()` or the project's relationship helper to declare owners; `recycle()` to share one parent; `sequence()` for varying attributes.
- Create only records that arrange behavior or support an assertion.

@if($pest)
## Datasets

Use a dataset when setup, body, and assertions are identical across inputs.

```php
it('forbids roles other than admin', function (Role $role) {
    actingAs(User::factory()->hasOrganization($role)->create())
        ->post('/settings')
        ->assertForbidden();
})->with(collect(Role::cases())->reject(fn (Role $role) => $role === Role::ADMIN));
```
@else
## Data Providers

Use a data provider when setup, body, and assertions are identical across inputs. Providers must be `public static`.

```php
public static function nonAdminRoles(): array
{
    return collect(Role::cases())
        ->reject(fn (Role $role): bool => $role === Role::ADMIN)
        ->mapWithKeys(fn (Role $role): array => [$role->value => [$role]])
        ->all();
}

#[DataProvider('nonAdminRoles')]
public function test_forbids_roles_other_than_admin(Role $role): void { ... }
```
@endif

Good fits: enum cases, roles and plans, boundary values, inputs invalid in the same way, input/output pairs. Cases needing different setup or assertions are separate tests; a body that branches is two tests.

@if($pest)
Name each dataset case for what differs so failures are identifiable.
@else
Key each provider case for what differs so failures are identifiable.
@endif
