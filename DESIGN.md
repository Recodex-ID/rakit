# Design Direction: Rakit

> **Authored by the agent, not the product owner.** It records the visual system Rakit
> inherited from Rewire (`resources/css/app.css`) and the choices made while building the
> ERP screens. Revise anything that doesn't match the owner's intent.

## Identity

A Laravel ERP starter kit: inventory, purchasing and sales on one stock ledger. The people
using it are warehouse staff, purchasing officers and admins who open it every working day.
The tone is **plain and dependable**: a tool you trust with stock numbers, not a product
trying to impress.

## Personality

- Direct and factual. Messages say what happened and why ("Not enough Copper wire in
  Jakarta: 2 on hand, 5 needed."), never a vague "Something went wrong".
- Dense where it helps. Tables, tabular numbers and short labels beat cards with big
  whitespace; people scan these screens many times a day.
- Calm. Colour is spent on status (draft, approved, delivered, short) and nothing else.

## Palette

Source of truth: `resources/css/app.css`. Do not introduce new colours outside this set.

| Token | Value | Role |
|---|---|---|
| `--color-brand-ink` | `#171717` | Primary buttons, sidebar, dark surfaces, body text |
| `--color-brand-graphite` | `#4d4d4d` | Hover/raised state on dark surfaces, secondary text on light |
| `--color-brand-orange` | `#f25623` | The one accent: on dark surfaces, chart bars |
| `--color-brand-orange-dark` | `#c2410c` | Orange text on light backgrounds (`#f25623` is only 3.4:1 on white) |
| `--color-brand-mist` | `#dedede` | Borders, muted text on dark surfaces |
| `--color-brand-snow` | `#fefefe` | Page background, text on dark surfaces |

Flux's `zinc` scale is rebased to pure neutral grays anchored on mist (200), graphite (600)
and ink (900).

Status badges use Flux's named colours, one meaning each: zinc = draft, amber = waiting for
someone, sky = approved/confirmed, green = done, red = cancelled or short.

## Typography

- **Display**: Space Grotesk (`--font-display`) for page headings and big numbers.
- **Body**: Instrument Sans (`--font-sans`).
- **Monospace**: JetBrains Mono (`--font-mono`) only for identifiers people copy or compare:
  document numbers, SKUs, codes.
- Every quantity and amount uses `tabular-nums` so columns line up.

## Motif

**The document number.** `PO-2026-0001` and `SO-2026-0001` appear in monospace everywhere a
document is referenced: list, detail heading, stock card, print, activity log. It is the
thread that ties a stock movement back to its cause.

## Dials

`Dial: ENERGY 1 / RHYTHM 1 / MOTION 1`

- **ENERGY 1**: quiet and utilitarian. Screens are tools.
- **RHYTHM 1**: the same list, form and detail layouts in every module, on purpose, so
  learning one module teaches the others.
- **MOTION 1**: Flux's own transitions (modals, toasts) and nothing else. No endless pulses.

## What NOT to do here

- Don't add a dark-mode toggle without a stated reason; only the sidebar is dark.
- Don't show made-up numbers. Dashboard figures come from the database; an empty state says
  what will fill it.
- Don't use colour alone to carry meaning: a short line shows "(short)" next to the red badge.
- Don't use em dashes in interface text.
