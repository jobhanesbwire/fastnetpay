# Dark Mode Fix

Dark mode now applies to:

- `html`
- `body`
- `.wrapper`
- AdminLTE sidebar
- treeview menus
- dropdowns, tables, cards, forms, and modal surfaces

The JavaScript keeps compatibility with the old `mode` localStorage key and also writes:

`fastnetpay-theme-mode`

## Files

- `assets/js/fastnetpay-ui.js`
- `ui/ui/scripts/fastnetpay-ui.js`
- `assets/css/fastnetpay-theme.css`
- `ui/ui/styles/fastnetpay-theme.css`

## Notes

AdminLTE has older skin selectors, so the custom theme layer uses explicit `.dark-mode .main-sidebar` overrides instead of editing vendor files.
