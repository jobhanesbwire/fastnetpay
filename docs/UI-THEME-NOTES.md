# FASTNETPAY Admin UI Theme Notes

## Files changed

- `ui/ui/styles/fastnetpay-theme.css` adds the FASTNETPAY theme layer for AdminLTE 2, Bootstrap 3, cards, tables, buttons, forms, modals, alerts, dashboard widgets, sidebar, navbar, dark mode, and responsive behavior.
- `ui/ui/admin/header.tpl` loads the theme after Bootstrap, AdminLTE, and the legacy PHPNuxBill styles. It also uses the existing default admin PNG directly when the admin has the default avatar, avoiding a missing thumbnail request.
- `ui/ui/scripts/fastnetpay-ui.js` adds the lightweight UI behavior for the dark-mode toggle, profile/search polish, keyboard-friendly search overlay, and SweetAlert toast defaults.
- `ui/ui/admin/admin/login.tpl` loads the same theme and adds a small FASTNETPAY badge without changing the login form action or fields.
- `ui/ui/customer/login.tpl`, `forgot.tpl`, and `register.tpl` use the modern FASTNETPAY auth shell while preserving the original form actions and field names.
- `ui/ui/admin/alert.tpl`, `ui/ui/admin/error.tpl`, `ui/ui/admin/404.tpl`, `ui/ui/customer/error.tpl`, and `ui/ui/customer/404.tpl` provide smaller modern alerts and friendly error pages. Debug traces are only shown outside `Live` mode.
- `ui/ui/admin/dashboard.tpl` wraps the dashboard in the FASTNETPAY dashboard shell and adds quick action links.
- `system/widgets/top_widget.php` and `ui/ui/widget/top_widget.tpl` provide refreshed dashboard metrics plus dynamic router statistics from `tbl_routers`, `tbl_user_recharges`, and `tbl_transactions`.
- `ui/ui/admin/settings/app.tpl`, `ui/ui/admin/footer.tpl`, `system/autoload/Text.php`, and `system/boot.php` support editable admin footer settings and safer footer HTML/URL output.
- `ui/ui/images/fastnetpay-wifi-favicon.svg` is the WiFi favicon used by the admin/customer static layouts.
- Root mirror files `assets/css/fastnetpay-theme.css` and `assets/js/fastnetpay-ui.js` are kept in sync for easier future packaging or frontend asset review.
- `fastnetpay-captive-portal/theme/fastnetpay-captive/assets/css/fastnetpay-captive.css` and `fastnetpay-captive-portal/mikrotik-hotspot/assets/css/fastnetpay-hotspot.css` use the updated FASTNETPAY background for the captive portal package.

## Brand colors

The theme variables live at the top of `ui/ui/styles/fastnetpay-theme.css`:

```css
--fnp-primary: #41a146;
--fnp-secondary: #f9c02b;
--fnp-bg-soft: #f1f1f1;
--fnp-dark: #1f2933;
--fnp-muted: #6b7280;
--fnp-white: #ffffff;
--fnp-danger: #dc3545;
--fnp-info: #0ea5e9;
```

The sidebar uses darker green shades derived from the primary color for better contrast. Yellow is used as an accent for active states, warnings, and highlights rather than as body text.

Background update for the M-Pesa STK Push work:

- Old page background: `#fdfac4`
- New page background: `#f1f1f1`
- Current search shows `#fdfac4` only in this historical note, not in active FASTNETPAY custom theme files.

## Adjusting colors later

Change the CSS variables in `:root` inside `ui/ui/styles/fastnetpay-theme.css`, then sync the root mirror with `cp ui/ui/styles/fastnetpay-theme.css assets/css/fastnetpay-theme.css`. Most controls, cards, tables, and status styles are tied to those variables, so normal brand changes should not require editing the templates.

## AdminLTE limitations

This project uses AdminLTE `v2.4.18` with Bootstrap `v3.4.1`. The template is class-based and older than modern AdminLTE releases, so the theme is intentionally an override layer instead of a rewrite. Some page-level templates still contain inline styles or older layout classes; the theme avoids changing those flows to preserve PHPNuxBill behavior.

## Validation notes

Tested locally with Docker on `http://localhost:8088`:

- Admin login loaded and submitted with the existing login flow.
- Customer login, forgot password, forgot username, register, and admin login pages loaded with the redesigned auth layouts.
- Dashboard loaded with the FASTNETPAY theme CSS, hidden dashboard title block, refreshed top cards, and router statistics section.
- Sidebar tree menus opened.
- Customer list/table rendered inside a responsive table wrapper.
- Customer add form loaded.
- Customer send-message Bootstrap modal opened and closed.
- Settings → General loaded with the new Footer Settings section.
- Error/alert templates were updated to use modern FASTNETPAY cards without changing their redirect/action behavior.
- Mobile width `390px` should collapse the auth pages, dashboard cards, and router grid to a single-column layout through the responsive rules in the theme.
- Route-level validation was run with `curl`; full browser console validation still requires a local browser/Playwright install.

The theme does not add external JavaScript or CDN dependencies.
