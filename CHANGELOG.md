# Changelog

All notable changes to the Extension are documented in this file.

## 2.2.0 - 02/07/2026
- Addition: the Help button in the plugin configuration now links to the Joomill documentation page (https://www.joomill-extensions.com/documentation/access-key-plugin)
- Addition: support for plugin lazy loading: on Joomla 6.1+ with PHP >= 8.4 the plugin class is loaded on demand when the event is dispatched; older Joomla/PHP versions keep the regular loading
- Improvement: the installer script now implements Joomla's InstallerScriptInterface (modern install path, Joomla 4.2+) instead of the deprecated legacy script pattern

## TODO

Planned for 2.3.0:
- Addition: option to also protect the Joomla Web Services API (api client), default off. Denied requests get a fixed 401 JSON:API response (configured failAction is ignored for the api client), the whitelist keeps working, and the key must be supplied on every API request because API clients do not persist a session cookie
- Addition: stealth mode as third failAction: serve the site's own 404 page so the administrator backend appears non-existent to scanners

Planned for 2.4.0:
- Addition: throttled email notification on denied backend access and optionally on successful access from an unknown IP
- Addition: integration with Joomla Action Logs so denied and successful access attempts appear in Users > Action Logs

Planned for 2.5.0 (touches the core design, needs a separate design decision first):
- Addition: multiple labelled keys instead of a single shared key, so keys can be handed out and revoked per person and the log shows which key was used; may shift the design from "param name is the secret" to key/value comparison
- Addition: validity window (start/end date) per key for temporary access without manual revocation
