@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$pest5 = $assist->hasPackage('pestphp/pest', '>=5.0');
@endphp
# Assertions

## Arrange, Act, Assert

Write each test with three parts. Put the setup first, then the one action under test, then the assertions. Put one blank line between the parts. A reader must find each part without a comment.

Keep each test complete. Do not use a value that another test makes.

## The Correct Assertion for Each Subject

Use the assertion in this table for each subject. The assertion in the table gives the clearest message when the test fails.

@if($pest)
| Subject | Assertion to use |
| --- | --- |
| A return value, the state of an object, or a transformation of a value | an `expect()` chain |
| An HTTP status, JSON, a session, or Inertia | a Laravel response assertion |
| The state in the database | a Laravel database assertion |
| The existence of a model | `assertModelExists($model)`, and not `assertDatabaseHas('users', ['id' => $user->id])` |

Use a PHPUnit assertion only if no Pest assertion and no Laravel assertion exists for the subject.
@else
| Subject | Assertion to use |
| --- | --- |
| A return value, the state of an object, or a transformation of a value | `assertSame()`, or the assertion for the type |
| An HTTP status, JSON, a session, or Inertia | a Laravel response assertion |
| The state in the database | a Laravel database assertion |
| The existence of a model | `assertModelExists($model)`, and not `assertDatabaseHas('users', ['id' => $user->id])` |

Use `assertSame()` and not `assertEquals()`, because `assertSame()` also compares the type.
@endif

Assert one fact one time. Do not assert a status of 200 before `assertSee`, because `assertSee` shows that the page rendered.

## The Assertion with a Name for a Response

Use the response assertion that has a name. Do not use a raw status code. A failure then gives the contract that is not correct.

| Assertion to use | Assertion to replace |
| --- | --- |
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |
| `assertUnauthorized()` | `assertStatus(401)` |
| `assertUnprocessable()` | `assertStatus(422)` |

@if($pest)
Keep one `expect()` chain on one subject. Start a new chain when the subject changes, or when the chain is difficult to read.
@else
Group the assertions for one subject together. Start a new group when the subject changes.
@endif

@if($pest5)
## The Expectation for a Format

Use the expectation that Pest gives for a format. Do not write a regular expression for a format that has an expectation, because the expectation gives a clearer message when the test fails.

Pest gives an expectation for an email address, a ULID, an IP address, a MAC address, a hostname, a domain, a Base64 value, and a hexadecimal value. Each one accepts `not` for the negative case. Search for the exact name before you use it.

@endif
## Assert a Known Value

Write the expected value in the test, or calculate the expected value by a different method. Do not calculate the expected value with the logic of the implementation, because the test then passes when that logic is wrong.

@if($pest)
```php
// The test calculates the value with the logic of the implementation.
$expected = now()->subHours(24)->floorSeconds(30)->toJson();
expect($from)->toBe($expected);

// The test sets a fixed input and asserts a known value.
travelTo('2025-01-01 00:00:00');
expect($from)->toBe('2024-12-31T00:00:00.000000Z');
```
@else
```php
// The test calculates the value with the logic of the implementation.
$expected = now()->subHours(24)->floorSeconds(30)->toJson();
$this->assertSame($expected, $from);

// The test sets a fixed input and asserts a known value.
$this->travelTo('2025-01-01 00:00:00');
$this->assertSame('2024-12-31T00:00:00.000000Z', $from);
```
@endif

## Assert the Complete Result

A status code is not the complete result of a write operation. Assert each item that follows, if the operation changes that item:

- the response or the return value
- the state in the database
- the jobs and the events that the operation dispatches
- the notifications and the mail that the operation sends

Assert on the failure path that the operation does not make these changes.

A test that asserts only `assertOk()` passes when the application saves no record.
