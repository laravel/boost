---
name: building-flux-interfaces
description: Building interfaces with Flux UI components for Laravel Livewire. Use when creating forms, modals, dropdowns, or other interactive UI elements.
---

# Building Flux Interfaces

## When to use this skill

Use this skill when the user asks about:
- Using Flux UI components
- Building forms with Flux
- Creating modals and dialogs
- Dropdown menus and navigation
- Interactive UI patterns with Livewire

## Installation

Flux UI is a component library for Livewire:

```bash
composer require livewire/flux
php artisan flux:install
```

## Buttons

```blade
{{-- Primary button --}}
<flux:button>Click me</flux:button>

{{-- Variants --}}
<flux:button variant="primary">Primary</flux:button>
<flux:button variant="secondary">Secondary</flux:button>
<flux:button variant="danger">Danger</flux:button>
<flux:button variant="ghost">Ghost</flux:button>

{{-- Sizes --}}
<flux:button size="sm">Small</flux:button>
<flux:button size="md">Medium</flux:button>
<flux:button size="lg">Large</flux:button>

{{-- With icons --}}
<flux:button icon="plus">Add Item</flux:button>
<flux:button icon="trash" variant="danger">Delete</flux:button>

{{-- Icon only --}}
<flux:button icon="pencil" size="sm" />

{{-- Loading state --}}
<flux:button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</flux:button>
```

## Form Inputs

```blade
{{-- Text input --}}
<flux:input wire:model="name" label="Name" placeholder="Enter your name" />

{{-- With error --}}
<flux:input wire:model="email" label="Email" type="email" :error="$errors->first('email')" />

{{-- Required --}}
<flux:input wire:model="username" label="Username" required />

{{-- With description --}}
<flux:input wire:model="slug" label="Slug" description="URL-friendly version of the title" />

{{-- Disabled --}}
<flux:input wire:model="readonly" label="Read Only" disabled />
```

## Textarea

```blade
<flux:textarea wire:model="content" label="Content" rows="5" />

<flux:textarea
    wire:model="bio"
    label="Biography"
    placeholder="Tell us about yourself..."
    :error="$errors->first('bio')"
/>
```

## Select

```blade
{{-- Basic select --}}
<flux:select wire:model="country" label="Country">
    <option value="">Select a country</option>
    <option value="us">United States</option>
    <option value="uk">United Kingdom</option>
    <option value="ca">Canada</option>
</flux:select>

{{-- With Livewire options --}}
<flux:select wire:model="category_id" label="Category">
    <option value="">Select category</option>
    @foreach($categories as $category)
        <option value="{{ $category->id }}">{{ $category->name }}</option>
    @endforeach
</flux:select>
```

## Checkbox and Radio

```blade
{{-- Checkbox --}}
<flux:checkbox wire:model="remember" label="Remember me" />

{{-- Checkbox group --}}
<flux:checkbox.group wire:model="notifications" label="Notifications">
    <flux:checkbox value="email" label="Email" />
    <flux:checkbox value="sms" label="SMS" />
    <flux:checkbox value="push" label="Push" />
</flux:checkbox.group>

{{-- Radio group --}}
<flux:radio.group wire:model="plan" label="Plan">
    <flux:radio value="free" label="Free" description="Basic features" />
    <flux:radio value="pro" label="Pro" description="All features" />
    <flux:radio value="enterprise" label="Enterprise" description="Custom solutions" />
</flux:radio.group>
```

## Switch

```blade
<flux:switch wire:model="active" label="Active" />

<flux:switch wire:model="notifications" label="Email notifications" description="Receive updates via email" />
```

## Modal

```blade
{{-- Trigger button --}}
<flux:button wire:click="$set('showModal', true)">Open Modal</flux:button>

{{-- Modal component --}}
<flux:modal wire:model="showModal">
    <flux:modal.header>
        <flux:heading>Edit Profile</flux:heading>
    </flux:modal.header>

    <flux:modal.body>
        <flux:input wire:model="name" label="Name" />
        <flux:input wire:model="email" label="Email" type="email" />
    </flux:modal.body>

    <flux:modal.footer>
        <flux:button wire:click="$set('showModal', false)" variant="ghost">
            Cancel
        </flux:button>
        <flux:button wire:click="save" variant="primary">
            Save Changes
        </flux:button>
    </flux:modal.footer>
</flux:modal>
```

## Dropdown

```blade
<flux:dropdown>
    <flux:button icon="ellipsis-vertical" variant="ghost" />

    <flux:dropdown.menu>
        <flux:dropdown.item wire:click="edit" icon="pencil">Edit</flux:dropdown.item>
        <flux:dropdown.item wire:click="duplicate" icon="document-duplicate">Duplicate</flux:dropdown.item>
        <flux:dropdown.separator />
        <flux:dropdown.item wire:click="delete" icon="trash" variant="danger">Delete</flux:dropdown.item>
    </flux:dropdown.menu>
</flux:dropdown>
```

## Card

```blade
<flux:card>
    <flux:card.header>
        <flux:heading size="lg">Card Title</flux:heading>
    </flux:card.header>

    <flux:card.body>
        <p>Card content goes here.</p>
    </flux:card.body>

    <flux:card.footer>
        <flux:button variant="primary">Action</flux:button>
    </flux:card.footer>
</flux:card>
```

## Alert / Notification

```blade
{{-- Inline alert --}}
<flux:alert variant="info">
    This is an informational message.
</flux:alert>

<flux:alert variant="success">
    Your changes have been saved!
</flux:alert>

<flux:alert variant="warning">
    Please review before continuing.
</flux:alert>

<flux:alert variant="danger">
    An error occurred.
</flux:alert>

{{-- Dismissible --}}
<flux:alert variant="success" dismissible>
    You can close this alert.
</flux:alert>
```

## Table

```blade
<flux:table>
    <flux:table.head>
        <flux:table.row>
            <flux:table.header>Name</flux:table.header>
            <flux:table.header>Email</flux:table.header>
            <flux:table.header>Status</flux:table.header>
            <flux:table.header></flux:table.header>
        </flux:table.row>
    </flux:table.head>

    <flux:table.body>
        @foreach($users as $user)
            <flux:table.row wire:key="{{ $user->id }}">
                <flux:table.cell>{{ $user->name }}</flux:table.cell>
                <flux:table.cell>{{ $user->email }}</flux:table.cell>
                <flux:table.cell>
                    <flux:badge :variant="$user->active ? 'success' : 'secondary'">
                        {{ $user->active ? 'Active' : 'Inactive' }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button icon="ellipsis-vertical" size="sm" variant="ghost" />
                        <flux:dropdown.menu>
                            <flux:dropdown.item wire:click="edit({{ $user->id }})">Edit</flux:dropdown.item>
                            <flux:dropdown.item wire:click="delete({{ $user->id }})" variant="danger">Delete</flux:dropdown.item>
                        </flux:dropdown.menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.body>
</flux:table>

{{ $users->links() }}
```

## Badge

```blade
<flux:badge>Default</flux:badge>
<flux:badge variant="primary">Primary</flux:badge>
<flux:badge variant="success">Success</flux:badge>
<flux:badge variant="warning">Warning</flux:badge>
<flux:badge variant="danger">Danger</flux:badge>

{{-- With dot indicator --}}
<flux:badge variant="success" dot>Online</flux:badge>
```

## Tabs

```blade
<flux:tabs wire:model="activeTab">
    <flux:tabs.list>
        <flux:tabs.tab value="general">General</flux:tabs.tab>
        <flux:tabs.tab value="security">Security</flux:tabs.tab>
        <flux:tabs.tab value="notifications">Notifications</flux:tabs.tab>
    </flux:tabs.list>

    <flux:tabs.panel value="general">
        <p>General settings content...</p>
    </flux:tabs.panel>

    <flux:tabs.panel value="security">
        <p>Security settings content...</p>
    </flux:tabs.panel>

    <flux:tabs.panel value="notifications">
        <p>Notification settings content...</p>
    </flux:tabs.panel>
</flux:tabs>
```

## Complete Form Example

```blade
<form wire:submit="save">
    <flux:card>
        <flux:card.header>
            <flux:heading>Create User</flux:heading>
        </flux:card.header>

        <flux:card.body class="space-y-4">
            <flux:input wire:model="name" label="Name" required />
            <flux:input wire:model="email" label="Email" type="email" required />

            <flux:select wire:model="role" label="Role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </flux:select>

            <flux:checkbox wire:model="sendWelcome" label="Send welcome email" />
        </flux:card.body>

        <flux:card.footer class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                Create User
            </flux:button>
        </flux:card.footer>
    </flux:card>
</form>
```

## Best Practices

1. **Use wire:model** - Bind inputs directly to Livewire properties
2. **Show validation errors** - Pass `:error="$errors->first('field')"`
3. **Loading states** - Use `wire:loading` for better UX
4. **Consistent variants** - Stick to the variant system
5. **Keyboard navigation** - Flux handles accessibility
6. **Mobile responsive** - Components adapt to screen size
