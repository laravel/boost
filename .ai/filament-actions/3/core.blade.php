## Filament Actions 3

### Important Note About Version 3

In Filament Actions v3, actions are **context-specific** and use different class namespaces depending on where they are used.
This is a key difference from v4, where actions are unified across all contexts.

**Always import the correct Action class for your context** to avoid errors and unexpected behavior.

### Types of Actions (Different Classes)

Each context requires a specific Action class with its own namespace:

#### 1. Page Actions (`Filament\Actions\Action`)

Used in custom Livewire components and panel pages.

**Capabilities:**
- Supports full modals with forms
- Can open URLs or execute custom logic

@verbatim
    <code-snippet name="Page Action Example" lang="php">
        use Filament\Actions\Action;
        use Filament\Actions\Concerns\InteractsWithActions;
        use Filament\Actions\Contracts\HasActions;
        use Filament\Forms\Concerns\InteractsWithForms;
        use Filament\Forms\Contracts\HasForms;
        use Livewire\Component;

        class ManagePost extends Component implements HasActions, HasForms
        {
            use InteractsWithActions;
            use InteractsWithForms;

            public function editAction(): Action
            {
                return Action::make('edit')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        // Form fields...
                    ])
                    ->action(function (array $data): void {
                        // Update logic...
                    });
            }
        }
    </code-snippet>
@endverbatim

#### 2. Table Actions (`Filament\Tables\Actions\Action`)

Used in table rows and headers. **Note:** This is a different class from page actions.

**Capabilities:**
- Supports modals
- Can be added to specific columns
- Can be row actions or header actions

@verbatim
    <code-snippet name="Table Action Example" lang="php">
        use Filament\Tables\Actions\Action;
        use Filament\Tables\Table;

        public function table(Table $table): Table
        {
            return $table
                ->columns([
                    // ...
                ])
                ->actions([
                    // Row actions - appear in each row
                    Action::make('edit')
                        ->icon('heroicon-m-pencil-square')
                        ->url(fn (Post $record): string => route('posts.edit', $record)),
                    Action::make('delete')
                        ->requiresConfirmation()
                        ->action(fn (Post $record) => $record->delete()),
                ])
                ->headerActions([
                    // Header actions - appear at the top of the table
                    Action::make('create')
                        ->icon('heroicon-m-plus')
                        ->url(route('posts.create')),
                ]);
        }
    </code-snippet>
@endverbatim

**Table-specific features:**
- Use `->actions()` for row actions
- Use `->headerActions()` for actions at the top of the table
- Row actions have access to the `$record` parameter

#### 3. Bulk Actions (`Filament\Tables\Actions\BulkAction`)

Special type of table action executed on multiple selected records at once.

**Important:** This is a separate class, not the regular `Action` class.

@verbatim
    <code-snippet name="Bulk Action Example" lang="php">
        use Filament\Tables\Actions\BulkAction;
        use Filament\Tables\Table;
        use Illuminate\Database\Eloquent\Collection;

        public function table(Table $table): Table
        {
            return $table
                ->columns([
                    // ...
                ])
                ->bulkActions([
                    BulkAction::make('delete')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->delete()),

                    BulkAction::make('publish')
                        ->icon('heroicon-m-check')
                        ->action(fn (Collection $records) => $records->each->publish()),
                ]);
        }
    </code-snippet>
@endverbatim

**Bulk action characteristics:**
- Receives a `Collection` of records, not a single record
- Appears when user selects multiple table rows
- Can have confirmation modals and forms

#### 4. Form Component Actions (`Filament\Forms\Components\Actions\Action`)

Actions attached to form components like text inputs, selects, etc.

@verbatim
    <code-snippet name="Form Component Action Example" lang="php">
        use Filament\Forms\Components\Actions;
        use Filament\Forms\Components\Actions\Action;
        use Filament\Forms\Components\TextInput;

        TextInput::make('email')
            ->suffixAction(
                Action::make('sendVerification')
                    ->icon('heroicon-m-envelope')
                    ->action(function () {
                        // Send verification email...
                    })
            ),

            Actions::make([
                Action::make('sendNotification')
                    ->icon('heroicon-m-bell')
                    ->action(function () {}),
            ])
    </code-snippet>
@endverbatim

**Common patterns:**
- `->suffixAction()` - Action at the end of the input
- `->prefixAction()` - Action at the start of the input
- `->hintAction()` - Action in the hint area

#### 5. Infolist Actions (`Filament\Infolists\Components\Actions\Action`)

Actions used within infolist entries.

@verbatim
    <code-snippet name="Infolist Action Example" lang="php">
        use Filament\Infolists\Components\Actions\Action;
        use Filament\Infolists\Components\TextEntry;

        TextEntry::make('status')
            ->badge()
            ->suffixAction(
                Action::make('changeStatus')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        // Form fields...
                    ])
                    ->action(function (array $data) {
                        // Update status...
                    })
            )
    </code-snippet>
@endverbatim

#### 6. Notification Actions (`Filament\Notifications\Actions\Action`)

Actions in notification messages. **Limited functionality.**

**Important limitations:**
- Does **not** support modals
- Can only open URLs or dispatch Livewire events

@verbatim
    <code-snippet name="Notification Action Example" lang="php">
        use Filament\Notifications\Actions\Action;
        use Filament\Notifications\Notification;

        Notification::make()
            ->title('New invoice created')
            ->body('Invoice #12345 has been created.')
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(route('invoices.show', 12345)),
                Action::make('markAsRead')
                    ->button()
                    ->dispatch('markAsRead', [12345]),
            ])
            ->send();
    </code-snippet>
@endverbatim

#### 7. Global Search Actions (`Filament\GlobalSearch\Actions\Action`)

Actions displayed in global search results. **Limited functionality.**

**Important limitations:**
- Does **not** support modals
- Can only open URLs or dispatch Livewire events

@verbatim
    <code-snippet name="Global Search Action Example" lang="php">
        use Filament\GlobalSearch\Actions\Action;

        public static function getGlobalSearchResultActions(Model $record): array
        {
            return [
                Action::make('edit')
                    ->url(static::getUrl('edit', ['record' => $record])),
            ];
        }
    </code-snippet>
@endverbatim

### Using Prebuilt Actions in Version 3

When using prebuilt actions (CreateAction, EditAction, etc.) in v3, import them from the context-specific namespace:

@verbatim
    <code-snippet name="Prebuilt Actions with Correct Namespaces" lang="php">
        // For pages
        use Filament\Actions\CreateAction;
        use Filament\Actions\DeleteAction;

        // For tables
        use Filament\Tables\Actions\CreateAction;
        use Filament\Tables\Actions\EditAction;
        use Filament\Tables\Actions\DeleteAction;
        use Filament\Tables\Actions\BulkActionGroup;
        use Filament\Tables\Actions\DeleteBulkAction;
    </code-snippet>
@endverbatim

### Upgrading to Version 4

If you're planning to upgrade to Filament Actions v4, be aware that:
- All action classes are unified into `Filament\Actions` namespace
- Tables have new toolbar actions area
- Bulk actions have new capabilities like chunking and individual authorization

See `.ai/filament-actions/4/core.blade.php` for v4-specific features.
