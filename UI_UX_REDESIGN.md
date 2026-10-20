# UI/UX Redesign Spec — Xtrafreaky

> Goal: a more modern, "innovative" UI where most flows live in **small popup/modal
> boxes** instead of separate full pages — inspired by the Fansly signup modal (clean
> centered card with tabs) and Cameo's compact login sheet. Keep the smart bits we
> already built (live username availability + live password validation); make the
> shell around them slicker and lighter.

Stack reality (don't fight it): **Blade + Bootstrap 5 + jQuery**, assets built by
Laravel Mix (Webpack). Bootstrap's Modal API is already used in the app
(`LoginModal.js`, age-verification dialog), so the modal-first direction is a natural
fit — no new framework needed.

---

## Design principles

1. **Modal-first.** If a flow is a single focused task (login, register, edit a field,
   confirm an action, small forms), it should open as a **popup modal** over the
   current page — not navigate to a new full page.
2. **Keep the smart inputs.** Live username-availability check and live password
   strength/match feedback stay exactly as they behave now — just restyled to fit the
   modal.
3. **Modern + lighter.** Centered card, generous spacing, rounded corners, one clear
   primary action, social-login row, tab switch between Login / Sign up (Fansly-style).
4. **Graceful fallback.** Every modal flow must still work as a real URL/page if opened
   directly (deep link, no-JS, SEO). The modal is the enhancement, not the only path.
5. **Don't break deep-linkable/SEO pages.** Profiles, content pages, checkout — these
   stay real pages (see "What should NOT be a modal").

---

## 1. Login / Register as a popup modal  ← priority

**Reference:** Fansly image — single centered white card, **"Sign up now | Login"**
tab toggle at top, stacked fields, big primary button, "Or" divider, social icons row.

**Current state**
- Login: [resources/views/auth/login-form.blade.php](resources/views/auth/login-form.blade.php)
- Register: [resources/views/auth/register-form.blade.php](resources/views/auth/register-form.blade.php) (full page, ~636 lines)
- A `LoginModal.js` already exists and does `$('#login-dialog').modal('show')`.

**Desired**
- One reusable **auth modal** with two tabs (Login / Sign up) that switch without a
  page reload.
- Triggered by the "Log in / Sign up" buttons anywhere in the app.
- Social login row (Apple / Google / Facebook / X — match whatever providers are
  actually configured; do not show providers we don't support).
- Still reachable at `/login` and `/register` as full pages (fallback). On those URLs,
  render the same card centered on a plain page.

**Keep**
- Username availability AJAX → `/api/check-username` (debounced, spinner, green check /
  red cross). [register-form.blade.php:599-635](resources/views/auth/register-form.blade.php#L599-L635)
- Password strength bar + requirement checklist + confirm-match.
  [register-form.blade.php:520-551](resources/views/auth/register-form.blade.php#L520-L551)

**Make better**
- Restyle the availability indicator to be cleaner inside the modal (inline pill, not
  cramped absolute icon).
- Submit via AJAX so validation errors appear **inside the modal** (no full-page
  reload, no jarring redirect).

## 2. Fix the "account creation error" page  ← priority / bug

**Symptom:** creating an account sometimes lands on an error page.

**Current state**
- `RegisterController@validator` + `registered()` —
  [app/Http/Controllers/Auth/RegisterController.php](app/Http/Controllers/Auth/RegisterController.php)
- Errors currently render inline on the page; a thrown exception during `User::create()`
  (lines ~218-242) re-throws and Laravel shows a generic error page.

**To do**
- Reproduce the exact error first (capture the real exception / stack from the daily
  log channel — the controller already logs it with user details + IP).
- Likely culprits to check: a required DB column with no default, a migration not run
  on the live server, reCAPTCHA/validation mismatch, or the crypto-wallet/seed hooks
  that fire on user creation in this codebase.
- Replace the hard failure with: catch → friendly error **inside the auth modal**, keep
  the user's entered data, log the real cause. No raw Laravel error page for users.

## 3. Improve username availability UX (keep, polish)

- Keep the endpoint and debounce. Improve feedback: show "Checking…", then a clear
  "✓ username is available" / "✗ taken — try `name123`" with a suggestion or two.
- Disable/enable the submit button based on availability + password validity so users
  can't submit a known-bad form.

## 4. TikTok homepage: vertical ↔ landscape toggle  ← net-new

**Current state**
- [resources/views/videos/reels.blade.php](resources/views/videos/reels.blade.php) +
  [app/Http/Controllers/FeedController.php](app/Http/Controllers/FeedController.php).
- Reacts to device orientation via CSS `@media (orientation: landscape)` only — there
  is **no manual button**.

**Desired**
- A visible toggle button on the reels UI to flip the player between **vertical
  (portrait)** and **landscape** layout on demand, independent of how the phone is held.
- Remember the choice (localStorage) so it persists between videos/sessions.
- Landscape mode: video fills width, overlay action buttons reflow so they don't cover
  the video.

## 5. Convert split-off pages into popup modals (the big one)

**Principle:** "Try to make every page a popup box if possible." Audit the app and move
single-task flows into modals. Each modal still backed by its existing route as a
fallback.

**Good candidates (small, single-task → modal):**
- Login / Sign up (see #1)
- Edit profile fields, change password, change email
- Settings sub-sections that are short forms
- Confirmations (delete, logout, "are you sure")
- Tips / send-message / small purchase confirmations
- Report / block dialogs
- Comments (already a sidebar on reels — could be a modal on other pages)

**What should NOT be a modal (keep as pages):**
- Public creator profiles & content pages (SEO + shareable links)
- The reels/feed itself (it IS the page)
- Long multi-step flows (full registration on a tiny screen, checkout) — modal is fine
  on desktop but must degrade to a page on mobile
- Anything that must be linkable/bookmarkable

**Approach:** build ONE reusable modal pattern (a Blade partial + small JS helper that
loads content into a Bootstrap modal, with an AJAX-or-navigate fallback), then migrate
flows onto it one at a time. Don't hand-roll a new modal per page.

---

## Build order (proposed)

1. ✅ **Auth modal** (Login/Sign up tabs) + keep username/password checks + AJAX errors. **DONE 2026-06-27** — see "Implementation log" below.
2. **Fix account-creation error** (find root cause from logs, friendly handling).  ← next
3. **Reusable modal helper** (the pattern everything else reuses).
4. **Reels vertical/landscape toggle.**
5. **Migrate small flows** to modals, one at a time, lowest-risk first.

## Decisions (locked 2026-06-27)

- **Start with:** the Auth modal (Login/Sign-up tabs). ← in progress
- **Look & feel:** keep the current **dark theme + purple `#830866`**, modernized into
  clean modal cards (not the Fansly white card — same layout, our colors).
- **Mobile:** auth stays a **modal even on phones** (full-screen-ish sheet on small
  widths, like the Fansly mobile screenshot), not a separate page.

## Still to confirm

- Which social logins are actually configured (Apple/Google/Facebook/X)? — I'll detect
  from config and only show wired-up providers.

## Implementation log

### Auth modal (step 1) — done 2026-06-27
- `resources/views/elements/modal-login.blade.php` — rewritten into a self-contained,
  card-less, **dark+purple** popup with **Log in / Sign up tabs**. On phones it becomes
  a full-screen sheet (per decision). Same `#login-dialog` id so all existing
  `data-target="#login-dialog"` triggers (subscribe buttons, post-locked) keep working.
- `resources/views/auth/modal-forms.blade.php` — reused as-is; provides login/register/
  forgot sections (keeps the live username-availability + password strength/match checks).
- `layouts/generic.blade.php` — modal + `LoginModal.js` now included **globally for
  guests** (plus reCAPTCHA JS when enabled, since the register form can now render on
  any page).
- `pages/profile.blade.php` — removed its local modal include + LoginModal.js (now global)
  to avoid duplicate `#login-dialog` / duplicate form IDs.
- `template/header.blade.php` — guest **Login / Sign Up** buttons now open the modal on
  the correct tab; real `/login` `/register` URLs kept as no-JS fallback.
- `public/js/LoginModal.js` — fixed a real bug (`window.reload()` → `window.location.reload()`
  that silently broke login success), and made `changeActiveTab` drive the tab highlight.
- `auth/login-form.blade.php` + `auth/register-form.blade.php` — `autofocus` suppressed
  in modal (`mode=ajax`) so the hidden modal doesn't grab focus on every page load.
- Verified: `php artisan view:cache` compiles all views with no Blade errors. Login &
  register both return JSON on AJAX, so the modal submits in-place and shows errors
  inline / via toast instead of a full page reload.

**Known follow-ups:** the register form is still long (many required fields + legal
checkboxes) — fine in a scrolling modal for now; streamlining it pairs with step 2
(the account-creation error). A `User::create()` failure now surfaces as a toast in the
modal instead of a raw error page, but the root cause still needs the step-2 fix.
