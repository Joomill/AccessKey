# Changelog

All notable changes to the Extension are documented in this file.

## 2.2.0 [Unreleased]
- Addition: the Help button in the plugin configuration now links to the Joomill documentation page (https://www.joomill-extensions.com/documentation/access-key-plugin)
- Addition: support for plugin lazy loading: on Joomla 6.1+ with PHP >= 8.4 the plugin class is loaded on demand when the event is dispatched; older Joomla/PHP versions keep the regular loading
- Improvement: the installer script now implements Joomla's InstallerScriptInterface (modern install path, Joomla 4.2+) instead of the deprecated legacy script pattern
