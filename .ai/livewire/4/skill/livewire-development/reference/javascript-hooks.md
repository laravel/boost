# Livewire 4 JavaScript Integration

Use `search-docs` for the full interceptor API.

- `Livewire.interceptMessage()` hooks a component's message lifecycle: `onFinish` (after response, before processing), `onSuccess` (`payload.snapshot`, `payload.effects`), `onError` (server errors).
- `Livewire.interceptRequest()` hooks the HTTP request: `onResponse`, `onSuccess`, `onError` (4xx/5xx, offers `preventDefault`), `onFailure` (network errors).
- Inside a component's `<script>`, `this.$intercept('save', ...)` scopes an interceptor to one action.
- `$errors` exposes validation errors to JavaScript.
