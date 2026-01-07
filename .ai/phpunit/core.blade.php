@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `{{ $assist->artisanCommand('make:test --phpunit {name}') }}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `{{ $assist->artisanCommand('test --compact') }}`.
- To run all tests in a file: `{{ $assist->artisanCommand('test --compact tests/Feature/ExampleTest.php') }}`.
- To filter on a particular test name: `{{ $assist->artisanCommand('test --compact --filter=testName') }}` (recommended after making a change to a related file).
