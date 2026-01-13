## Inertia v2

- Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Deferred props.
- Infinite scrolling using merging props and `WhenVisible`.
- Lazy loading data on scroll.
- Polling.
- Prefetching.

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

### Client-Side Patterns (Forms, Navigation, etc.)
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
- When working with Inertia client-side code (forms, navigation, components), activate: `inertia-react-development`.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
- When working with Inertia client-side code (forms, navigation, components), activate: `inertia-vue-development`.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- When working with Inertia client-side code (forms, navigation, components), activate: `inertia-svelte-development`.
@endif
