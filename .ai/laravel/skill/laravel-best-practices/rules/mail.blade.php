@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Mail Best Practices

## Queue Slow Delivery

Implement `ShouldQueue` (with `Queueable, SerializesModels`) on the mailable; it queues even when called via `Mail::send()`. Keep mail synchronous when the caller must know whether delivery was accepted, or no worker is available.

## Queued Mail After Commit

A queued mailable dispatched inside a transaction can be processed before commit. Use `->afterCommit()` on the mailable, or the connection's `after_commit` option, when the mail depends on committed records. On rollback it is not dispatched. It does not defer synchronous delivery.

## Assert the Delivery Mode

A mailable implementing `ShouldQueue` needs `Mail::assertQueued()`; `Mail::assertSent()` fails for it. Use `assertSent()` only for synchronous mail.

## Markdown Mailables

Good for conventional transactional messages (HTML plus plain text, publishable themes): `{{ $assist->artisanCommand('make:mail OrderShipped --markdown=mail.orders.shipped') }}`. Use a custom HTML/text pair for specialized designs.

## Separate Content and Delivery Tests

Test content by instantiating the mailable with `assertSeeInHtml()` / `assertSeeInText()`. Test delivery separately with `Mail::fake()` so failures identify the cause.
