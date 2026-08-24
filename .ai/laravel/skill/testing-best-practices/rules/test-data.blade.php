@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Factories and Test Data

## Each Test Makes Its Own Data

@if($pest)
Make the data that a test changes inside that test. Do not make a record in a `beforeEach()` at the top of the file, because a reader of the test then cannot see the setup, two tests can then change the same record, and you cannot then give one test a different factory state.

Use `beforeEach()` only for a configuration that applies to every test in the file, such as a feature flag or the configuration of a service.
@else
Make the data that a test changes inside that test. Do not make a record in `setUp()`, because a reader of the test then cannot see the setup, two tests can then change the same record, and you cannot then give one test a different factory state.

Write a private method that makes the record, and call the method from each test that needs it. `setUp()` runs for every test in the class, and the objects that it makes stay in memory until the suite ends. A local variable in a private method is released after the test.

Use `setUp()` only for a configuration that applies to every test in the class, such as a feature flag or the configuration of a service.
@endif

## The Construction of a Record

- Use `create()` if the test needs the record in the database.
- Use `make()` only if the test does not need the database. Examples are the render of a notification and the behavior of a value object.
- Use a factory state with a name instead of a raw attribute. The call `User::factory()->unverified()->create()` gives the meaning of the state. The call `create(['email_verified_at' => null])` gives only the value.
- Use `for()` or the relationship helper of the project to declare the owner of a record.
- Use `recycle()` if several records must share one parent record.
- Use `sequence()` if several records need different attributes.

```php
$organization = Organization::factory()->onPlan(BillingPlan::PRO)->create();

$environment = Environment::factory()->recycle($organization)->create();

$organizations = Organization::factory()
    ->count(3)
    ->sequence(
        ['created_at' => now()->setSeconds(30)],
        ['created_at' => now()->setSeconds(1)],
    )
    ->create();
```

Make only the records that the test asserts on. A nested call to `create()` that makes an extra record adds time to every run of the suite.

@if($pest)
## The Datasets

Use a dataset if the body, the setup, and the assertions are the same for each input value, and only the input value changes.

```php
it('forbids roles other than admin', function (Role $role) {
    actingAs(User::factory()->hasOrganization($role)->create())
        ->post('/settings')
        ->assertForbidden();
})->with(collect(Role::cases())->reject(fn (Role $role) => $role === Role::ADMIN));
```
@else
## The Data Providers

Use a data provider if the body, the setup, and the assertions are the same for each input value, and only the input value changes.

```php
public static function nonAdminRoles(): array
{
    return collect(Role::cases())
        ->reject(fn (Role $role): bool => $role === Role::ADMIN)
        ->mapWithKeys(fn (Role $role): array => [$role->value => [$role]])
        ->all();
}

#[DataProvider('nonAdminRoles')]
public function test_forbids_roles_other_than_admin(Role $role): void
{
    $this->actingAs(User::factory()->hasOrganization($role)->create())
        ->post('/settings')
        ->assertForbidden();
}
```

Declare each data provider method as `public static`.
@endif

Use this method for these input values:

- the cases of an enum
- the roles and the plans
- the boundary values
- the input values that are not valid in the same way
- the pairs of an input value and an output value

Write separate tests if the cases need a different setup, a different behavior, or different assertions. One test function with a branch in the body is two tests in one function.

@if($pest)
Give each case in the dataset a name that states the difference. A failure then names the case, and you do not count the positions.
@else
Give each case in the data provider a key that states the difference. A failure then names the case, and you do not count the positions.
@endif
