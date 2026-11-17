# Visual & style guardrails

Decisions from design discussion that don't live in any functional spec, because they're about look and feel, not behavior. Keep this file updated as scope changes — the "current feature-flag state" section especially, since it'll go stale fast.

## Overall tone

Reference tool, not a delivery marketplace. The app's job is recording and recalling quantities, not selling food — it should read closer to a utility/logbook than to Ubereats/DoorDash. Minimal chrome, no upsell patterns, no "browse and buy" visual language.

## Imagery

- **No photos anywhere** — not restaurants, not dishes, not users. Photos read as "marketplace" and are expensive to source per restaurant; the app doesn't need them to do its job.
- Where visual identification genuinely helps (e.g. a cuisine cue on the Restaurant view header), use simple icons — an emoji-style or line-icon treatment per cuisine/dish category — never a photo placeholder standing in for a future photo.

## Layout patterns by screen state

- **No marketing/landing content.** Home's hero search is the entire pitch, logged in or out. No scrolly explainer sections above or below it, no "why use this app" copy.
- **Read-only order view**: full-width, single continuous scroll, nothing pinned or sticky. It's a page you read top to bottom.
- **Order create (edit) view**: the opposite — a control panel (search/add items) scrolls at the top, while the current item list and save action stay pinned to the bottom of the viewport. Built for assembling, not reading.
- **Notes**: always collapsed by default behind a one-line toggle ("+ Add a note"). Never render as an open field with equal visual weight to the item list or menu browser.
- **A slider driving a live calculation and a list filter are always separate controls**, even when they're both "party size" in the same screen. Don't make a calculator slider silently filter a list too — use an explicit, separately-labeled filter for that.

## Current feature-flag state — deferred, don't build UI for these yet

- **Verdict UI** — badges, capture prompts, "awaiting your verdict" banners. Not built. Don't add a verdict badge to any card or view until this is revisited.
- **Alternate/combo suggestions** ("see other ways to order") — deferred along with the weighted calculator. `GuidanceSummary` shows a single average only, no expandable alternates section.
- **Guest/device-linked order creation** — deferred. Creating an order requires login; viewing one doesn't.

## Reference, not source of truth for visuals

`plateful-wireframes.html` shows structure and flow only — its gray-box, monospace, dashed-border look is a wireframing convention, not the intended visual design. For actual Tailwind decisions (typography, spacing, color, icon set), follow the project's normal frontend design conventions rather than replicating the wireframe's appearance.