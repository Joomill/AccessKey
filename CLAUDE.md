# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Joomla 4/5 **system plugin** ("Access Key") that locks the administrator login area behind a secret URL key. Visitors to the backend must append the configured key as a URL parameter (e.g. `?yoursecretkey`) or be allowed through by IP whitelist; otherwise they get a message or redirect. Published by Joomill Extensions.

There is no build system, package manager, or test suite. The repo *is* the installable plugin. The only "artifact" is `Access Key.zip` (gitignored), produced by zipping the repo contents for distribution/install into Joomla.

## How the plugin works (request flow)

The plugin subscribes to `onAfterInitialise` (`src/Extension/Accesskey.php`). On every request it:

1. Returns immediately if the session flag `accesskey` is already set (key was accepted earlier this session).
2. Returns if no `key` param is configured (logs a warning), or if the request is **not** the administrator client. Site frontend is never gated.
3. Detects the visitor IP via `IpHelper::getVisitorIp()` and checks it against the comma-separated `whitelist` param (`IpHelper::isIpInWhitelist`, supports exact IPs and CIDR). Whitelisted IPs are let through; if they did not also supply the key, a non-blocking info message is enqueued (`showWhitelistMessage`).
4. Otherwise checks whether the configured key name is present in the request input. If yes, sets the session flag and continues. If no, calls `handleAccessDenied()`.

`handleAccessDenied()` branches on the `failAction` param: `message` throws an `AccessKeyException` that is caught locally to set an HTTP status header, echo the message, and `close()` the app; `redirect` sends the user to `redirectUrl` (falling back to `Uri::root()`).

Note: the key check is presence-only. The param **name** is the secret (`input->get($this->params->get('key'))` returns non-null when `?<keyname>` is in the URL); the param value is not compared.

## Architecture / namespace layout

PSR-4 namespace root `Joomill\Plugin\System\Accesskey` maps to `src/` (declared in `accesskey.xml` `<namespace path="src">`). The plugin is instantiated through Joomla's DI container in `services/provider.php`, not via the legacy plugin constructor convention.

- `src/Extension/Accesskey.php` — the plugin class (event subscriber, access decision logic).
- `src/Helper/IpHelper.php` — IP detection, sanitization (anti header-injection), CIDR/whitelist matching. Reads IP from a prioritized list of proxy headers (`X-Forwarded-For` first, `REMOTE_ADDR` last).
- `src/Field/IpField.php` — custom form field (`type="ip"`) that displays the current visitor IP read-only in the plugin config screen. Registered via `addfieldprefix` in `accesskey.xml`.
- `src/Exception/AccessKeyException.php` — typed exception with named static factories (`unauthorized`, `ipDetectionFailed`, etc.) carrying HTTP status codes.
- `services/provider.php` — `ServiceProviderInterface` that wires the plugin into the container.
- `script.php` — install/uninstall script (`plgSystemAccesskeyInstallerScript`): enforces minimum PHP/Joomla versions in `preflight`, renders the Joomill branding/thank-you screen in `postflight`.
- `accesskey.xml` — the manifest: metadata, config fields, files list, update server URLs.
- `language/<locale>/plg_system_accesskey[.sys].ini` — translations. `.sys.ini` is for manifest-level strings (name/description shown in the extension list); the plain `.ini` is for runtime/config strings.

## Conventions

- Target Joomla 5.0+ (minimum enforced in `script.php`) and the namespace-based plugin architecture (the v2.0.0 migration in git history). The codebase is being made Joomla 6 ready. Do not reintroduce legacy non-namespaced patterns or deprecated APIs (`$app->input`, `Factory::getLanguage()`, the `Factory` service locator inside the plugin).
- Every class/method carries a docblock with `@since` tags and the GPL file header. Match the existing Joomla coding-standard formatting (tabs in `services/provider.php`, 4-space elsewhere as present). New members (classes, methods, properties) get the `@since` of the release that introduces them, not the project's first version. Members carried over from an earlier release keep their original `@since`. The current release is 2.1.0.
- All errors are logged through `Log::add(..., 'accesskey')` (the `accesskey` category), never surfaced as raw PHP errors. Preserve this pattern.
- Bumping the version means editing `<version>` in `accesskey.xml`; user-facing strings live in the language `.ini` files, not hardcoded (except a few English fallbacks).

## Distribution

To produce an installable package, zip the repo contents (excluding `.git`, `.idea`, `.junie`, and existing zips per `.gitignore`) into `Access Key.zip`. Joomla installs/updates it as a system plugin; updates are served from the Joomill update server declared in `accesskey.xml`.
