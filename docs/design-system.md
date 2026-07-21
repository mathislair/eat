# eat — Cartoon Design System

A vivid, playful, sticker-book UI language for `eat`. The look is built from a
few consistent ideas:

- **Chunky rounded shapes** — generous border radii (`rounded-xl2`, `rounded-blob`).
- **A thick "ink" outline** on every solid element (`border-3 border-ink`).
- **Hard offset shadows** instead of soft blurs (`shadow-cartoon*`), like a sticker.
- **Bouncy interactions** — things lift on hover and press down on click.

> **See it live:** visit [`/design-system`](/design-system) for a rendered
> gallery of every token and component.

## Where things live

| Concern | File |
| --- | --- |
| Colour / font / radius / shadow / animation **tokens** | `tailwind.config.js` |
| Runtime tokens (light + dark) & **component classes** | `resources/css/app.css` |
| Web fonts (Fredoka + Nunito) | `resources/views/app.blade.php` |
| Live showcase page | `resources/js/Pages/DesignSystem.vue` |

## Tokens

### Colour

Brand scales are available as Tailwind utilities (`bg-punch-500`, `text-grape-600`, …):

| Token | Role |
| --- | --- |
| `punch` | Primary action (coral / tangerine) |
| `sunny` | Highlights, hosts |
| `mint` | Success / calm |
| `grape` | Links & focus rings |
| `berry` | Danger / love |
| `sky` | Informational |
| `ink` | Outlines & primary text |
| `cream` | Warm page background |

Surface, text and shadow colours flip for dark mode automatically via CSS
variables (`--ds-surface`, `--ds-text`, `--ds-shadow`, …) defined in
`resources/css/app.css` under `prefers-color-scheme`.

### Typography

- **Fredoka** (`font-display`) — headings, buttons, badges. Rounded and chunky.
- **Nunito** (`font-sans`) — body copy. Applied to `<body>` by default.

### Shape, shadow & motion

- Radius: `rounded-xl2` (1.25rem), `rounded-blob` (2rem).
- Border: `border-3` (3px) for the ink outline.
- Shadow: `shadow-cartoon-xs|sm|(base)|lg|xl` — hard, ink-coloured, no blur.
- Animation: `animate-wiggle`, `animate-pop-in`, `animate-float`.

## Component classes

Reusable primitives defined in `@layer components`. Prefer these over
re-implementing the look inline:

| Class | Use |
| --- | --- |
| `.btn` + `.btn-primary` / `.btn-secondary` / `.btn-danger` / `.btn-mint` / `.btn-ghost` | Buttons |
| `.card`, `.card-interactive`, `.panel` | Surfaces (interactive lifts on hover) |
| `.input` | Text inputs, selects, textareas |
| `.label` | Form labels |
| `.checkbox` | Checkboxes |
| `.badge` + `.badge-host` / `.badge-mint` / `.badge-grape` / `.badge-sky` | Pills |

The shared Vue components (`PrimaryButton`, `SecondaryButton`, `DangerButton`,
`TextInput`, `InputLabel`, `Checkbox`) already wrap these classes, so most pages
get the style for free.

## Example

```vue
<div class="card card-interactive">
    <h3 class="font-display text-lg font-bold text-ink dark:text-cream">Team dinner</h3>
    <span class="badge badge-host">Host</span>
    <PrimaryButton>Join</PrimaryButton>
</div>
```

## Extending

1. Add colour/shadow/animation **tokens** in `tailwind.config.js`.
2. Add a reusable **component class** in the `@layer components` block of
   `resources/css/app.css` when a pattern repeats across pages.
3. If you compose brand utilities dynamically (e.g. `bg-${name}-${shade}`), add
   the pattern to `safelist` in `tailwind.config.js` so it isn't purged.
