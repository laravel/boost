## Filament Actions 4

### Important Version 4 Changes

#### Unified Actions

Actions are now fully unified across tables, forms, infolists and regular actions. 
Instead of having separate Action classes for each context, all actions now use a single `Filament\Actions` namespace.

#### Toolbar actions

Tables now support a dedicated toolbar actions area.

@verbatim
    <code-snippet name="Filament action example" lang="php">
        use Filament\Tables\Table;

        public function table(Table $table): Table
        {
            return $table
                ->toolbarActions([
                    // ...
                ]);
        }
    </code-snippet>
@endverbatim

This is useful for actions like "create", which are not tied to a specific row, or for making bulk actions more visible and accessible in the table’s toolbar.

#### Bulk Actions

- Bulk actions now support the `chunkSelectedRecords()` method, allowing selected records to be processed in smaller batches instead of loading everything into memory at once — improving performance and reducing memory usage with large datasets.
- You can now use `authorizeIndividualRecords()` to check a policy method for each selected record in a bulk action. Only the records the user is authorized to act on will be included in the $records array.
- You can now display a notification after a bulk action completes to inform users of the outcome — especially useful when some records are skipped due to authorization.
    - Use `successNotificationTitle()` when all records are processed successfully.
    - Use `failureNotificationTitle()` to show a message when some or all records fail.
    - Both methods can accept a function to display the number of successful and failed records using `$successCount` and `$failureCount`.

#### Rate limiting actions

You can now use the `rateLimit()` method to limit how often an action can be triggered — per user IP, per minute.

#### Authorization

Authorization messages can now be shown in action tooltips and notifications.

@verbatim
    <code-snippet name="Filament action example" lang="php">
        use Filament\Actions\Action;

        Action::make('edit')
            ->url(fn (): string => route('posts.edit', ['post' => $this->post]))
            ->authorize('update')
    </code-snippet>
@endverbatim

#### Import action

Importing relationships: `BelongsToMany` relationships can now be imported via actions.

#### Export action

- You can now customize the styling of individual cells in XLSX exports using the `makeXlsxRow()` and `makeXlsxHeaderRow()` methods in your exporter class.
- You can now configure the OpenSpout XLSX writer in your exporter class:
    - Use `getXlsxWriterOptions()` to set export options like column widths.
    - Override `configureXlsxWriterBeforeClosing()` to modify the writer instance before it's finalized.

#### Tooltips for disabled buttons

You can now display `tooltips` on disabled buttons.

#### Testing actions

Testing actions in v4 is now simpler and more streamlined.

@verbatim
    <code-snippet name="Filament action testing example" lang="php">
        use Filament\Actions\Testing\TestAction;
        use function Pest\Livewire\livewire;

        $invoice = Invoice::factory()->create();

        livewire(ManageInvoices::class)
            ->callAction(TestAction::make('send')->arguments(['invoice' => $invoice->getKey()]));

        livewire(ManageInvoices::class)
            ->assertActionVisible(TestAction::make('send')->arguments(['invoice' => $invoice->getKey()]))

        livewire(ManageInvoices::class)
            ->assertActionExists(TestAction::make('send')->arguments(['invoice' => $invoice->getKey()]))
    </code-snippet>
@endverbatim