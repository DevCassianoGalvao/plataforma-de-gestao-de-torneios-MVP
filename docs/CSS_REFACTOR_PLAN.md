# CSS Refactor Plan

## Current load and risk

`app/Views/layouts/base.php` loads the legacy base and nine CSS files: `app.css`, `tokens.css`, `themes.css`, `layout.css`, `components.css`, `dashboard.css`, `management.css`, `public-portal.css`, `foundation.css`, and `operation.css`.

The divergence audit and source inspection identify overlapping ownership of root tokens, dark-mode selectors, body/page styles, buttons, cards/panels, sidebars, tables and public navigation. The final rendered rule depends on cascade order rather than a declared component contract. This is a blocker for page-by-page reconstruction.

## Asset disposition

| File | Current role | Decision | Condition |
|---|---|---|---|
| `tokens.css` | Color, type, spacing tokens | Keep and rewrite selectively | Become single token source; remove duplicates elsewhere. |
| `themes.css` | Light/dark overrides | Keep and consolidate | Only theme variable overrides; no page selectors. |
| `foundation.css` | Reset, focus, motion | Keep and consolidate | Own accessibility baseline only. |
| `layout.css` | Shell/sidebar/layout | Rewrite | Own administrative/public shells and responsive layout primitives only. |
| `components.css` | Generic components | Rewrite incrementally | Own button, input, badge, table, dialog, empty/loading states. |
| `app.css` | Earlier global styles | Freeze, then remove | Remove only after all selectors have a mapped replacement. |
| `dashboard.css` | Dashboard overrides | Migrate then remove | Replace with dashboard component styles colocated in one layer. |
| `management.css` | Generic CRUD styles | Freeze | Retire when generic CRUD leaves product navigation. |
| `operation.css` | Match-operation overrides | Migrate then remove | Replace after dedicated match center acceptance. |
| `public-portal.css` | Portal page rules | Rewrite | Own public portal composition after new templates exist. |

## Target cascade

1. `tokens.css`: raw and semantic variables only.
2. `themes.css`: `[data-theme]` and championship CSS variable override values only.
3. `foundation.css`: reset, typography baseline, focus, reduced motion.
4. `layout.css`: shell, grids, containers, responsive structural rules.
5. `components.css`: reusable components and their state variants.
6. One scoped page stylesheet per reconstructed area only while the area is being migrated.

No new stylesheet may redefine `:root`, `body`, global theme variables or generic `.button`, `.card`, `.panel`, `.sidebar` selectors already owned by earlier layers.

## Consolidation executed - 2026-07-28

- `app/Views/layouts/base.php` no longer loads `public/assets/css/app.css`. This removes the measured duplicate owners for `:root`, `body`, `.button`, `.panel`, `.sidebar` and the original public-shell rules from the rendered cascade.
- `app.css` remains versioned but frozen as a rollback artifact. It was not deleted because browser regression evidence has not yet covered every legacy administrative route.
- Active ownership is now: `tokens.css` for variables, `themes.css` for dark variables/status values, `foundation.css` for baseline/a11y, `layout.css` for shell/grid, `components.css` for reusable controls, and page files only for their scoped compositions.
- `dashboard.css`, `management.css`, `operation.css` and `public-portal.css` remain loaded because their respective journeys still have incomplete migration acceptance. They must not redefine raw tokens or body styles.

## Audit work before CSS changes

1. Generate selector-to-file report for every loaded stylesheet.
2. Mark identical selectors and divergent declarations, especially `:root`, dark theme, body, buttons, cards, navigation and tables.
3. Capture browser screenshots for old and migrated pages at 320, 375, 768, 1024, 1440 and 1920 pixels.
4. Define component ownership and remove selector duplication before a second page consumes the component.
5. Test keyboard focus, contrast, overflow, reduced motion and no-JavaScript form usability for each migrated page.

## Migration sequence

- Establish shell and tokens without changing product page content.
- Migrate login/global dashboard and remove their old selectors.
- Migrate assisted management pages and remove `management.css` dependencies page by page.
- Migrate match center separately; do not reuse table-expansion CSS.
- Migrate public portal after public templates/presenters are split.
- When no rendered page uses a legacy selector, remove it in a dedicated commit with browser regression evidence.

## Inline style and JavaScript rules

- Replace page-specific `style` attributes with semantic classes or safe championship custom properties.
- Championship colors are validated server-side and applied only as scoped custom properties (`--champ-*`).
- JavaScript toggles state classes/ARIA attributes; it must not build arbitrary style strings or control business authorization.
- Confirmations attach to the submitted form/action consistently, not only an arbitrary nested button.

## Measured selector evidence - 2026-07-27

The following collisions were measured with `rg` on the files actually linked by
`app/Views/layouts/base.php`; they are not hypothetical design-system risks.

| Selector | Loaded owners | Required decision |
|---|---|---|
| `:root` | `app.css`, `layout.css`, `tokens.css` | `tokens.css` becomes the only raw/semantic token owner. |
| `body` | `app.css`, `tokens.css`, `components.css`, `layout.css`, `foundation.css`, `dashboard.css` | `foundation.css` owns baseline; page files cannot reset it. |
| `.button` | `app.css`, `components.css`, `dashboard.css`, `operation.css` | `components.css` owns variants; page files may only compose named variants. |
| `.panel` | `app.css`, `components.css`, `layout.css`, `public-portal.css` | Split structural container from reusable component before a migrated page uses it. |
| `.sidebar` | `app.css`, `layout.css` | `layout.css` owns shell/drawer behavior. |
| `[data-theme="dark"]` | `app.css`, `themes.css`, `foundation.css` | `themes.css` owns variable values; foundation can consume but not redefine them. |

`public/assets/js/app.js` is the only loaded interaction script. It owns no business
authorization: server authorization remains in `AuthPolicy`, `ScopeService` and the
controllers. Do not remove a legacy stylesheet until every route that relies on it
has a browser screenshot and keyboard/mobile regression result.
