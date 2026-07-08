# Dark Mode Theme Fix

FASTNETPAY dark mode now uses separate accessible variables instead of reusing light colors directly.

Light variables:

```css
--fnp-primary: #41a146;
--fnp-secondary: #f9c02b;
--fnp-bg: #f1f1f1;
```

Dark variables:

```css
--fnp-primary: #4ade80;
--fnp-primary-dark: #22c55e;
--fnp-secondary: #facc15;
--fnp-bg: #0f172a;
--fnp-card-bg: #172033;
--fnp-text: #e5e7eb;
--fnp-muted: #aab4c3;
```

Affected areas:

- `html`, `body`, and `.wrapper` dark-mode state classes;
- admin dashboard;
- sidebar and active menu items;
- header, profile dropdown, and tenant pill;
- cards, tables, forms, modals, alerts, dropdowns;
- SaaS Management screens.

Yellow/gold is used as an accent and button background with dark text where needed. It should not be used as small body text on bright surfaces.
