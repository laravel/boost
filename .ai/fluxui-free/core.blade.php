## Flux UI Free

- This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use the `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:
@verbatim
<code-snippet name="Flux UI Component Example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>
@endverbatim

### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip
</available-flux-components>

### Available Icon Names
Flux UI uses Heroicons for its icon library. Use icons with the correct syntax and valid icon names only.

**Syntax for icons in buttons:**
- Leading icon: <flux:button icon="plus">Create</flux:button>
- Trailing icon: <flux:button icon:trailing="chevron-down">Open</flux:button>
- Icon only: <flux:button icon="x-mark" />
- Control variant: <flux:button icon="arrow-down" icon:variant="solid">Download</flux:button>

**Syntax for standalone icons:**
- Basic: <flux:icon.user />
- With variant: <flux:icon.bolt variant="solid" />
- Dynamic: <flux:icon :name="$icon" variant="mini" />

<available-icon-names>
academic-cap, adjustments-horizontal, adjustments-vertical, archive-box, archive-box-arrow-down, archive-box-x-mark, arrow-down, arrow-down-circle,
    arrow-down-left, arrow-down-on-square, arrow-down-on-square-stack, arrow-down-right, arrow-down-tray, arrow-left, arrow-left-circle,
    arrow-left-end-on-rectangle, arrow-left-start-on-rectangle, arrow-long-down, arrow-long-left, arrow-long-right, arrow-long-up,
    arrow-path, arrow-path-rounded-square, arrow-right, arrow-right-circle, arrow-right-end-on-rectangle, arrow-right-start-on-rectangle,
    arrow-top-right-on-square, arrow-trending-down, arrow-trending-up, arrow-turn-down-left, arrow-turn-down-right, arrow-turn-left-down,
    arrow-turn-left-up, arrow-turn-right-down, arrow-turn-right-up, arrow-turn-up-left, arrow-turn-up-right, arrow-up, arrow-up-circle,
    arrow-up-left, arrow-up-on-square, arrow-up-on-square-stack, arrow-up-right, arrow-up-tray, arrow-uturn-down, arrow-uturn-left, arrow-uturn-right,
    arrow-uturn-up, arrows-pointing-in, arrows-pointing-out, arrows-right-left, arrows-up-down, at-symbol, backspace, backward, banknotes, bars-2,
    bars-3, bars-3-bottom-left, bars-3-bottom-right, bars-3-center-left, bars-4, bars-arrow-down, bars-arrow-up, battery-0, battery-50, battery-100,
    beaker, bell, bell-alert, bell-slash, bell-snooze, bold, bolt, bolt-slash, book-open, bookmark, bookmark-slash, bookmark-square, briefcase, bug-ant,
    building-library, building-office, building-office-2, building-storefront, cake, calculator, calendar, calendar-date-range, calendar-days,
    camera, chart-bar, chart-bar-square, chart-pie, chat-bubble-bottom-center, chat-bubble-bottom-center-text, chat-bubble-left,
    chat-bubble-left-ellipsis, chat-bubble-left-right, chat-bubble-oval-left, chat-bubble-oval-left-ellipsis, check, check-badge, check-circle,
    chevron-double-down, chevron-double-left, chevron-double-right, chevron-double-up, chevron-down, chevron-left, chevron-right, chevron-up,
    chevron-up-down, circle-stack, clipboard, clipboard-document, clipboard-document-check, clipboard-document-list, clock, cloud, cloud-arrow-down,
    cloud-arrow-up, code-bracket, code-bracket-square, cog, cog-6-tooth, cog-8-tooth, command-line, computer-desktop, cpu-chip, credit-card,
    cube, cube-transparent, currency-bangladeshi, currency-dollar, currency-euro, currency-pound, currency-rupee, currency-yen, cursor-arrow-rays,
    cursor-arrow-ripple, device-phone-mobile, device-tablet, divide, document, document-arrow-down, document-arrow-up, document-chart-bar,
    document-check, document-currency-bangladeshi, document-currency-dollar, document-currency-euro, document-currency-pound, document-currency-rupee,
    document-currency-yen, document-duplicate, document-magnifying-glass, document-minus, document-plus, document-text, ellipsis-horizontal,
    ellipsis-horizontal-circle, ellipsis-vertical, envelope, envelope-open, equals, exclamation-circle, exclamation-triangle, eye, eye-dropper,
    eye-slash, face-frown, face-smile, film, finger-print, fire, flag, folder, folder-arrow-down, folder-minus, folder-open, folder-plus, forward,
    funnel, gif, gift, gift-top, globe-alt, globe-americas, globe-asia-australia, globe-europe-africa, h1, h2, h3, hand-raised, hand-thumb-down,
    hand-thumb-up, hashtag, heart, home, home-modern, identification, inbox, inbox-arrow-down, inbox-stack, information-circle, italic, key, language,
    lifebuoy, light-bulb, link, link-slash, list-bullet, lock-closed, lock-open, magnifying-glass, magnifying-glass-circle, magnifying-glass-minus,
    magnifying-glass-plus, map, map-pin, megaphone, microphone, minus, minus-circle, moon, musical-note, newspaper, no-symbol, numbered-list,
    paint-brush, paper-airplane, paper-clip, pause, pause-circle, pencil, pencil-square, percent-badge, phone, phone-arrow-down-left,
    phone-arrow-up-right, phone-x-mark, photo, play, play-circle, play-pause, plus, plus-circle, power, presentation-chart-bar, presentation-chart-line,
    printer, puzzle-piece, qr-code, question-mark-circle, queue-list, radio, receipt-percent, receipt-refund, rectangle-group, rectangle-stack,
    rocket-launch, rss, scale, scissors, server, server-stack, share, shield-check, shield-exclamation, shopping-bag, shopping-cart, signal,
    signal-slash, slash, sparkles, speaker-wave, speaker-x-mark, square-2-stack, square-3-stack-3d, squares-2x2, squares-plus, star, stop, stop-circle,
    strikethrough, sun, swatch, table-cells, tag, ticket, trash, trophy, truck, tv, underline, user, user-circle, user-group, user-minus, user-plus,
    users, variable, video-camera, video-camera-slash, view-columns, viewfinder-circle, wallet, wifi, window, wrench, wrench-screwdriver, x-circle, x-mark
</available-icon-names>
