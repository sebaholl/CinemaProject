# storage/

Runtime files the app writes. Not web-readable (see `.htaccess`).

## storage/mail

When `config/config.php` has `'mail' => ['driver' => 'file']`, every email the site
"sends" is written here as a `.eml` file instead of going out over the network.

That is the local-development setting: XAMPP on macOS has no working sendmail, so
`mail()` silently does nothing. Writing the file means you can actually see and
demo the real message.

To read one:
- open `mailbox.php` in the browser (only works while the driver is `file`), or
- double-click the `.eml` file - Mail opens it exactly as a recipient would see it.

On the live server, switch the driver to `'mail'` and nothing is written here.
