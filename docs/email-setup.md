# Ticket confirmation email — setup

When a mock checkout succeeds, `checkout.php` builds a `Ticket` and emails it to the
customer. The same `Ticket` is shown on `confirmation.php`, so the feature can be
demoed without a mail client.

A failed email never fails a booking: the seats are already paid for and locked, so
the error is logged and the customer still gets the confirmation page.

## Choosing how mail is sent

One setting in `config/config.php` → `mail.driver`:

| Driver | What it does | When to use it |
|---|---|---|
| `file` | Writes the finished message to `storage/mail/` as a `.eml` | **Default.** Local development. Read it with `mailbox.php` |
| `api`  | Posts the email to Mailtrap over HTTPS | **To actually receive mail here.** See below |
| `smtp` | Talks to a mail server over a socket | A network that allows outbound SMTP |
| `mail` | PHP's `mail()` | The live server, where sendmail is configured |

### Why not just `smtp`?

The SMTP client (`src/SmtpClient.php`) is written and tested — 22 tests, including a
full conversation against a fake server in `tests/helpers/fake_smtp_server.php`.

It cannot get out of this network. The TCP connection opens and the server sends its
greeting, then `EHLO` never gets a reply. Checked on ports 25, 587, 465 and 2525,
against both Mailtrap and Gmail, from the CLI and from Apache. HTTPS to the same
hosts works fine. That is an ISP/router-level block on SMTP, which is common on home
and campus networks — not a bug, and not a Mailtrap problem.

Hence the `api` driver: Mailtrap also accepts mail over plain HTTPS, which is open.

## Setting up the `api` driver (5 minutes)

1. Sign up at <https://mailtrap.io> (free). It gives you a sandbox inbox: mail sent
   there is caught and displayed instead of delivered, so a test booking can never
   email a real person by accident.
2. Go to **Email Testing → your inbox → Integrations**, and pick **API** (not SMTP).
3. That page shows a send URL ending in your inbox id, and an `Api-Token`.
4. Set up your local config. **The token never goes in `config/config.php`** —
   that file is committed. It goes in `config/local.php`, which `.gitignore`
   excludes:

   ```
   cp config/local.example.php config/local.php
   ```

   Then edit `config/local.php` and put your token in `mail.api.token`. If your
   inbox id differs from the one in `config.php`, set `mail.api.endpoint` there too.
5. Check it:

   ```
   php tests/send_test_email.php
   ```

   It prints what it is about to do, sends one ticket email, and tells you exactly
   what to fix if the settings are incomplete. Then look in your Mailtrap inbox.

   To send a real booking instead of a made-up one:

   ```
   php tests/send_test_email.php 16
   ```

## Reading mail with the `file` driver

`mailbox.php` lists everything in `storage/mail/` and previews it. It requires a
login and 404s unless the driver is `file`, so it does not exist on the live server.
You can also just double-click a `.eml` file — Mail opens it as a recipient sees it.

`storage/mail/` must be writable by Apache (which runs as `daemon`):

```
chmod 777 storage storage/mail
```

Without that, sends throw, the booking still succeeds, and the log says
`Mail spool directory is not writable`.

## config/local.php — secrets and per-machine settings

`config/config.php` is committed and holds everything safe to share. Anything that
must not reach GitHub (API tokens, a real database password) or that differs between
machines (`base_url`, `site_url`) goes in `config/local.php`, which is git-ignored.

It returns an array shaped like `$config`, and only the keys it sets are replaced —
`array_replace_recursive` merges nested arrays, so this is a complete file:

```php
<?php
return [
    'mail' => ['api' => ['token' => 'your-token-here']],
];
```

Two consequences worth knowing:

- **A teammate without the file is not broken.** The token falls back to empty, the
  send fails with a clear message, and the booking still succeeds — they just see the
  "could not email" notice. `php tests/send_test_email.php` tells them what to do.
- **`base_url` can live here too.** It is the setting that keeps changing between
  branches (`/CinemaProject` vs `/DWP/CinemaProject`). Set it once in `local.php` and
  stop editing `config.php` for it. The merge runs before `BASE_URL` is defined.

## On the live server

Switch the driver to `mail` (or keep `api` with a real Mailtrap sending domain) and
set `site_url` to the real address — emails contain absolute links and the poster
image, which do not resolve from a path alone.

## The classes

| Class | Responsibility |
|---|---|
| `Ticket` | The data for one booking's ticket. No SQL, no HTML |
| `TicketMailer` | Turns a `Ticket` into a subject and two bodies, using the templates |
| `Mailer` | Builds the MIME message and picks a driver. Knows nothing about cinemas |
| `SmtpClient` | The SMTP conversation over a socket |
| `MailtrapApiClient` | The HTTPS POST to Mailtrap |
| `TicketCode` | Derives `LUM-00042-7F3A` from a booking id with an HMAC |
| `QrCode` | Builds the QR image URL |

Content and transport are deliberately separate: changing the wording means touching
only `templates/email/`, and changing how mail is sent means touching only a driver.
`Mailer` is reusable as-is for the contact form in build step 7.

## Tests

```
php tests/ticket_email_test.php    # 45 — ticket code, Mailer, both templates
php tests/smtp_client_test.php     # 22 — full SMTP conversation vs a fake server
php tests/mailtrap_api_test.php    # 23 — API request and every failure mode
php tests/send_test_email.php      # sends one real email with the current driver
```
