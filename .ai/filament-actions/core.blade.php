## Filament Actions

### Overview

Filament Actions are a powerful feature that handle "doing something" within your application. Unlike traditional actions in web development, Filament Actions are specifically designed for user interfaces. They encapsulate the complete user workflow: the trigger (button/link), the interactive modal window (if needed), and the logic that executes when the action is completed.

For example, a delete action might display a button that opens a confirmation modal, and when confirmed, executes the deletion logic. This entire workflow is defined as a single Action object.

Actions can be used throughout your Filament application:
- Custom Livewire components and panel pages
- Table rows and headers
- Form components
- Infolists
- Notifications
- Global search results

@verbatim
    <code-snippet name="Basic Filament Action Example" lang="php">
        use Filament\Actions\Action;

        Action::make('delete')
            ->requiresConfirmation()
            ->action(fn () => $this->record->delete())
    </code-snippet>
@endverbatim

### Key Features

#### Action Types and Styles

Actions can be displayed in different visual styles:

@verbatim
    <code-snippet name="Action Display Styles" lang="php">
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
            ->labeledFrom('md')
    </code-snippet>
@endverbatim

#### Prebuilt Actions

Filament provides prebuilt actions for common Eloquent operations with sensible defaults:

- **CreateAction** - Create new records
- **EditAction** - Edit existing records
- **ViewAction** - View records (read-only)
- **DeleteAction** - Delete records
- **ReplicateAction** - Duplicate records
- **ForceDeleteAction** - Permanently delete soft-deleted records
- **RestoreAction** - Restore soft-deleted records
- **ImportAction** - Import data from files
- **ExportAction** - Export data to files

These prebuilt actions can be customized to fit your specific needs.

#### Authorization and Visibility

Control when actions are visible or enabled based on permissions or other conditions:

@verbatim
    <code-snippet name="Authorization and Visibility" lang="php">
        use Filament\Actions\Action;

        // Show/hide conditionally
        Action::make('edit')
            ->visible(auth()->user()->can('update', $this->post))
            ->hidden(! auth()->user()->can('update', $this->post))

        // Disable (keeps visible but inactive)
        Action::make('delete')
            ->disabled(! auth()->user()->can('delete', $this->post))
            ->disabledTooltip('You do not have permission to delete this post')
    </code-snippet>
@endverbatim

#### Confirmation Modals

Actions can require confirmation before executing:

@verbatim
    <code-snippet name="Confirmation Modal" lang="php">
        use App\Models\Post;
        use Filament\Actions\Action;

        Action::make('delete')
            ->action(fn (Post $record) => $record->delete())
            ->requiresConfirmation()
            ->modalHeading('Delete post')
            ->modalDescription('Are you sure you want to delete this post? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete it')
            ->modalIcon('heroicon-o-trash')
            ->modalIconColor('danger')
    </code-snippet>
@endverbatim

#### Modal Forms

Actions can open modals with forms to collect user input:

@verbatim
    <code-snippet name="Modal with Form" lang="php">
        use App\Models\Post;
        use App\Models\User;
        use Filament\Actions\Action;
        use Filament\Forms\Components\Select;

        Action::make('updateAuthor')
            ->schema([
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

#### Wizard Actions

Create multi-step workflows within action modals:

@verbatim
    <code-snippet name="Wizard Action" lang="php">
        use Filament\Actions\Action;
        use Filament\Forms\Components\Wizard\Step;

        Action::make('create')
            ->steps([
                Step::make('Basic Information')
                    ->schema([
                        // Form fields...
                    ]),
                Step::make('Details')
                    ->schema([
                        // Form fields...
                    ]),
                Step::make('Review')
                    ->schema([
                        // Form fields...
                    ]),
            ])
    </code-snippet>
@endverbatim

#### Custom Modal Content

Add custom views to action modals:

@verbatim
    <code-snippet name="Custom Modal Content" lang="php">
        use Filament\Actions\Action;

        Action::make('showDetails')
            ->modalContent(view('filament.actions.details'))
            ->modalContentFooter(view('filament.actions.footer'))
    </code-snippet>
@endverbatim

#### Customizing Modal Actions

Control the footer actions in modals:

@verbatim
    <code-snippet name="Custom Modal Actions" lang="php">
        use Filament\Actions\Action;
        use Filament\Actions\StaticAction;

        Action::make('help')
            ->modalContent(view('filament.help'))
            ->modalCancelAction(fn (StaticAction $action) => $action->label('Close'))
            ->modalSubmitAction(false) // Remove submit button

        // Add extra footer actions
        Action::make('create')
            ->form([
                // Form fields...
            ])
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('createAnother', arguments: ['another' => true]),
            ])
            ->action(function (array $data, array $arguments): void {
                // Create record...

                if ($arguments['another'] ?? false) {
                    // Reset form without closing modal
                }
            })
    </code-snippet>
@endverbatim

### Using Actions in Livewire Components

Integrate Filament Actions into custom Livewire components:

@verbatim
    <code-snippet name="Action in Livewire Component" lang="php">
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

            // Method name must match action name or end with 'Action'
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

Render the action in your Blade view:

@verbatim
    <code-snippet name="Rendering Actions in Blade" lang="blade">
        <div>
            {{-- Render the action --}}
            {{ $this->deleteAction }}

            {{-- Required for modals (once per component) --}}
            <x-filament-actions::modals />
        </div>
    </code-snippet>
@endverbatim

The `<x-filament-actions::modals />` component injects the HTML required to render action modals. Include it once per Livewire component, regardless of how many actions you have.

#### Passing Arguments to Actions

Pass data to actions when rendering:

@verbatim
    <code-snippet name="Passing Arguments" lang="blade">
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
    <code-snippet name="Receiving Arguments" lang="php">
        Action::make('delete')
            ->action(function (array $arguments) {
                $post = Post::find($arguments['post']);
                $post?->delete();
            })
    </code-snippet>
@endverbatim

#### Checking Action Visibility

Check if an action is visible before rendering:

@verbatim
    <code-snippet name="Checking Visibility" lang="blade">
        <div>
            {{-- Check visibility before rendering --}}
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

#### Grouping Actions

Group multiple actions together:

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

#### Triggering Actions Programmatically

Trigger actions using Livewire's wire methods:

@verbatim
    <code-snippet name="Triggering with wire:click" lang="blade">
        <button wire:click="mountAction('delete', { id: 12345 })">
            Delete
        </button>
    </code-snippet>
@endverbatim

@verbatim
    <code-snippet name="Triggering with JavaScript" lang="js">
        $wire.mountAction('delete', { id: 12345 })
    </code-snippet>
@endverbatim

#### Chaining Actions

Chain multiple actions together, where one action can trigger another:

@verbatim
    <code-snippet name="Chaining Actions" lang="php">
        use Filament\Actions\Action;

        Action::make('first')
            ->requiresConfirmation()
            ->action(function () {
                // First action logic...
            })
            ->extraModalFooterActions([
                Action::make('second')
                    ->requiresConfirmation()
                    ->action(function () {
                        // Second action logic...
                    }),
            ])
    </code-snippet>
@endverbatim

Use `->cancelParentActions()` to close the parent action's modal when triggering a chained action.

### Testing Actions

Test actions using Pest or PHPUnit with Livewire's testing utilities:

@verbatim
    <code-snippet name="Testing Actions" lang="php">
        use function Pest\Livewire\livewire;

        it('can send invoices', function () {
            $invoice = Invoice::factory()->create();

            livewire(EditInvoice::class, [
                'invoice' => $invoice,
            ])
                ->callAction('send')
                ->assertHasNoActionErrors();

            expect($invoice->refresh())
                ->isSent()->toBeTrue();
        });

        it('can delete posts with confirmation', function () {
            $post = Post::factory()->create();

            livewire(ManagePosts::class)
                ->callAction('delete', ['post' => $post->id])
                ->assertHasNoActionErrors();

            expect(Post::find($post->id))->toBeNull();
        });
    </code-snippet>
@endverbatim

### Version-Specific Features

For detailed information about features specific to each version:
- See `.ai/filament-actions/3/core.blade.php` for Filament Actions v3 specifics
- See `.ai/filament-actions/4/core.blade.php` for Filament Actions v4 specifics and what's new
