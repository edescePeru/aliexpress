# REAL NEXT 8 — Public Access Flow Contract

## Route contract

| Surface | Route | Middleware | Controller / view |
| --- | --- | --- | --- |
| Welcome | `GET /` | `web` | `welcome2` |
| Login | `GET /login` | `web`, `guest` | `Auth\LoginController@showLoginForm` |
| Login submit | `POST /login` | `web`, `guest` | Laravel `AuthenticatesUsers` |
| Home | `GET /home` | `web`, `auth` | `HomeController@index` |
| Logout | `POST /logout` | `web` | Laravel `AuthenticatesUsers` |

The redesign does not change routes, controllers, guards, credentials, session handling, CSRF, authorization, or redirects.

## Authentication contract

- Login keeps `email`, `password`, and `remember` as the submitted field names.
- `email` uses `autocomplete="email"`; `password` uses `autocomplete="current-password"`.
- Field validation errors and the password-reset status message remain visible and accessible.
- Password recovery is shown only while `password.request` exists.
- Logout remains a CSRF-protected `POST` request.
- `/home` remains protected by `auth` middleware.
- Dashboard access continues to honor `access_dashboard`; platform administrators use `platform.dashboard`.

## Visual contract

The three target views use `layouts.publicAccess` and `venti-public.css` as one reusable public-access pattern:

- a calm visual panel using only `public/landing/img/logo_venti.png`;
- a functional panel for copy, forms, actions, errors, and footer;
- one type, spacing, radius, border, focus, and button system aligned with Venti Next;
- a single-column mobile composition with no global horizontal overflow.

The reference image informed only the split composition and visual hierarchy. It was not copied literally.

## Legacy elements removed

- AdminLTE `login-box`, `login-card-body`, icon input groups, and generic card styling;
- duplicated welcome/home markup;
- permanent inline JavaScript for logout;
- obsolete `logo_pdf.png` use in the three target views;
- commented legacy login implementation.

## Functional note

The prior visible `Recordarme` checkbox had no `name`, so the browser never submitted it. The redesign restores the existing Laravel contract with `name="remember"` and does not change backend behavior.

## Revisit triggers

- a future backend-provided brand asset or product tagline;
- a change to the password-recovery route;
- a new authenticated landing policy for users without `access_dashboard`;
- localization requirements beyond the current Spanish copy.
