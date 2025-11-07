## Filament Actions 3

### What is an Action?

In Filament, actions also handle "doing" something in your app. However, they are a bit different from traditional
actions.
They are designed to be used in the context of a user interface. For instance, you might have a button to delete a
client record, which opens a modal to confirm your decision.
When the user clicks the "Delete" button in the modal, the client is deleted. This whole workflow is an "action".

@verbatim
    <code-snippet name="Filament action example" lang="php">
        use Filament\Actions\Action;

        Action::make('delete')
        ->requiresConfirmation()
        ->action(fn () => $this->client->delete())
    </code-snippet>
@endverbatim

### Types of Actions (Different Classes)

Each context uses a specific class:

1. `Filament\Actions\Action` - Custom Livewire components and panel pages
- Supports full modals
- Can open URLs or execute logic

2. `Filament\Tables\Actions\Action` - Table actions (rows/header)
- Supports modals
- Can be added to specific columns

3. `Filament\Tables\Actions\BulkAction` - Bulk actions
- Executed on multiple selected records

4. `Filament\Forms\Components\Actions\Action` - Actions in form components

5. `Filament\Infolists\Components\Actions\Action` - Actions in infolists

6. `Filament\Notifications\Actions\Action` - Actions in notifications
- Does not support modals (only URL or Livewire events)

7. `Filament\GlobalSearch\Actions\Action` - Actions in global search results
- Does not support modals (only URL or Livewire events)

### Prebuilt Actions

Filament provides prebuilt actions for common Eloquent operations:

- CreateAction - Create new records
- EditAction - Edit existing records
- ViewAction - View records (read-only)
- DeleteAction - Delete records
- ReplicateAction - Duplicate records
- ForceDeleteAction - Permanently delete (soft deletes)
- RestoreAction - Restore deleted records (soft deletes)
- ImportAction - Import data
- ExportAction - Export data

These actions come with sensible default configurations and can be customized as needed.

### Examples
@verbatim
    <code-snippet name="Filament action examples" lang="php">
        use Filament\Actions\Action;

        // Button (default) - with background
        Action::make('edit')->button()

        // Link - no background, link appearance
        Action::make('edit')->link()

        // Icon Button - icon only
        Action::make('edit')
        ->icon('heroicon-m-pencil-square')
        ->iconButton()

        // Responsive - icon button on mobile, button with label on desktop
        Action::make('edit')
        ->icon('heroicon-m-pencil-square')
        ->button()
        ->labeledFrom('md') // Label from md breakpoint
    </code-snippet>
@endverbatim

### Authorization and Visibility

Filament actions support conditional visibility and disabling based on authorization or other conditions.

@verbatim
    <code-snippet name="Filament action visibility" lang="php">
        // Show/hide conditionally
        Action::make('edit')
        ->visible(auth()->user()->can('update', $this->post))
        ->hidden(! auth()->user()->can('update', $this->post))

        // Disable (keeps visible but inactive)
        Action::make('delete')
        ->disabled(! auth()->user()->can('delete', $this->post))
    </code-snippet>
@endverbatim

### Confirmation Modal

Filament actions can require confirmation before executing:

@verbatim
    <code-snippet name="Filament action confirmation" lang="php">
        use App\Models\Post;
        use Filament\Actions\Action;

        Action::make('delete')
        ->action(fn (Post $record) => $record->delete())
        ->requiresConfirmation()
        ->modalHeading('Delete post')
        ->modalDescription('Are you sure? This cannot be undone.')
        ->modalSubmitActionLabel('Yes, delete it')
        ->modalIcon('heroicon-o-trash')
        ->modalIconColor('danger')
    </code-snippet>
@endverbatim

### Modal with Form

Filament actions can open modals with forms to collect user input:

@verbatim
    <code-snippet name="Filament action modal with form" lang="php">
        use App\Models\Post;
        use App\Models\User;
        use Filament\Actions\Action;
        use Filament\Forms\Components\Select;

        Action::make('updateAuthor')
            ->form([
                Select::make('authorId')
                    ->label('Author')
                    ->options(User::query()->pluck('name', 'id'))
                    ->required(),
            ])
            ->action(function (array $data, Post $record): void {
                $record->author()->associate($data['authorId']);
                $record->save();
            })
    </code-snippet>
@endverbatim

### Wizard in Modal

You can create multi-step wizards within action modals:

@verbatim
    <code-snippet name="Filament action wizard" lang="php">
        use Filament\Actions\Action;
        use Filament\Forms\Components\Wizard\Step;

        Actions\Action::make('create')
            ->steps([
                Step::make('Step1')
                    ->schema([
                        // ...
                    ]),
                Step::make('Step2')
                    ->schema([
                        // ...
                    ]),
            ])
    </code-snippet>
@endverbatim

### Custom Modal Content

Filament actions allow for custom content in modals:

@verbatim
    <code-snippet name="Filament action custom modal content" lang="php">
        use Filament\Actions\Action;

        Action::make('advance')
            ->modalContent(view('filament.pages.actions.advance'))
            ->modalContentFooter(view('filament.pages.actions.footer'))
    </code-snippet>
@endverbatim

### Modal Actions

Filament actions support customizing modal actions:

@verbatim
    <code-snippet name="Filament action custom footer actions" lang="php">
        use Filament\Actions\Action;
        use Filament\Actions\StaticAction;

        Action::make('help')
            ->modal()
            ->modalDescription('...')
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Close'))
            ->modalSubmitAction(false), // Remove submit button
    </code-snippet>
@endverbatim

@verbatim
    <code-snippet name="Filament action custom footer actions" lang="php">
        use Filament\Actions\Action;

        // Extra footer actions
        Action::make('create')
            ->form([
                // ...
            ])
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('createAnother', arguments: ['another' => true]),
            ])
            ->action(function (array $data, array $arguments): void {
                
                // Create record

                if ($arguments['another'] ?? false) {
                    // Reset form without closing modal
                }
            }),
    </code-snippet>
@endverbatim

### Chaining Actions

Filament actions can chain multiple actions together, allowing one action to trigger another.

@verbatim
    <code-snippet name="Filament action chaining" lang="php">
        use Filament\Actions\Action;

        Action::make('first')
            ->requiresConfirmation()
            ->action(function () {})
            ->extraModalFooterActions([
                Action::make('second')
                    ->requiresConfirmation()
                    ->action(function () {}),
            ]),
    </code-snippet>
@endverbatim

You can use `->cancelParentActions('first')` on the second action to close the first action's modal when the second action is triggered.

### Using Actions in Livewire Components

Filament actions can be integrated into custom Livewire components. Here's how to set up and use actions within a Livewire component:

@verbatim
    <code-snippet name="Filament action rendering" lang="php">
        use App\Models\Post;
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

            public Post $post;

            // The method name must be: {actionName} or {actionName}Action
            public function deleteAction(): Action
            {
                return Action::make('delete')
                    ->requiresConfirmation()
                    ->action(fn () => $this->post->delete());
            }

            public function render()
            {
                return view('livewire.manage-post');
            }
        }
    </code-snippet>
@endverbatim

**Important**: The method must share the exact same name as the action, or the name followed by `Action`.

Now, to render the action in your Livewire component's Blade view:

@verbatim
    <code-snippet name="Filament action rendering" lang="blade">
        <div>
            {{-- Render action --}}
            {{ $this->deleteAction }}

            {{-- Required for modals (once per component) --}}
            <x-filament-actions::modals />
        </div>
    </code-snippet>
@endverbatim

You also need `<x-filament-actions::modals />` which injects the HTML required to render action modals. 
This only needs to be included within the Livewire component once, regardless of how many actions you have for that component.


### Passing Arguments

You can pass arguments to actions when rendering them:

@verbatim
    <code-snippet name="Filament action rendering" lang="blade">
        <div>
            @foreach ($posts as $post)
                <h2>{{ $post->title }}</h2>

                {{ ($this->deleteAction)(['post' => $post->id]) }}
            @endforeach

            <x-filament-actions::modals />
        </div>
    </code-snippet>
@endverbatim

@verbatim
    <code-snippet name="Filament action rendering" lang="php">
        Action::make('delete')
            ->action(function (array $arguments) {
                $post = Post::find($arguments['post']);

                $post?->delete();
            });
    </code-snippet>
@endverbatim

### Visibility in Livewire

@verbatim
    <code-snippet name="Check Action Visibility" lang="blade">
        <div>
            {{-- Check if action is visible before rendering --}}
            @if ($this->deleteAction->isVisible())
                {{ $this->deleteAction }}
            @endif

            {{-- With arguments --}}
            @if (($this->deleteAction)(['post' => $post->id])->isVisible())
                {{ ($this->deleteAction)(['post' => $post->id]) }}
            @endif
        </div>
    </code-snippet>
@endverbatim

### Grouping Actions

You can group multiple actions together for better organization and presentation.

@verbatim
    <code-snippet name="Grouping Actions" lang="blade">
        <div>
            <x-filament-actions::group :actions="[
                $this->editAction,
                $this->viewAction,
                $this->deleteAction,
            ]" />

            <x-filament-actions::modals />
        </div>
    </code-snippet>
@endverbatim

### Triggering Programmatically

You can trigger actions programmatically using Livewire's `$wire` or `wire:click` directives.

@verbatim
    <code-snippet name="Triggering Actions" lang="blade">
        <button wire:click="mountAction('test', { id: 12345 })">
            Button
        </button>
    </code-snippet>
@endverbatim

@verbatim
    <code-snippet name="Triggering Actions" lang="js">
        $wire.mountAction('test', { id: 12345 })
    </code-snippet>
@endverbatim

### Testing Actions

Filament uses Pest for testing. However, you can easily adapt this to PHPUnit.

@verbatim
    <code-snippet name="Filament action rendering" lang="php">
        use function Pest\Livewire\livewire;

        it('can send invoices', function () {
            $invoice = Invoice::factory()->create();

            livewire(EditInvoice::class, [
                'invoice' => $invoice,
            ])
                ->callAction('send');

            expect($invoice->refresh())
                ->isSent()->toBeTrue();
        });
    </code-snippet>
@endverbatim