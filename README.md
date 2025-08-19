# Roundcube Matomo Analytics

This plugin integrates **Matomo Analytics** into Roundcube by injecting the official Matomo tracking snippet on rendered pages.

## Why this exists

Older Roundcube plugins targeted **Piwik** (Matomo's former name). This plugin is a clean Matomo-first replacement with simple configuration and privacy-aware defaults.

---

## Features

- Injects Matomo tracking script on all Roundcube pages.
- Optional **Do Not Track** (DNT) honoring.
- Optionally **disable tracking for authenticated users**.
- Composer-installable, using `composer/installers`.
- Zero changes required to Roundcube templates.

---

## Installation

### A) Composer (recommended)

From your Roundcube installation root:

```bash
composer require texxasrulez/roundcube-matomo-analytics
```

This will install the plugin into `plugins/matomo_analytics/`.

### B) Manual installation

1. Download the release archive.
2. Extract into your Roundcube `plugins/` directory so it looks like:

```
roundcubemail/
└── plugins/
    └── matomo_analytics/
        ├── matomo_analytics.php
        ├── config.inc.php.dist
        └── composer.json
```

---

## Configuration


> **Note on Matomo URL:** You may set `matomo_analytics_url` to a full URL (`https://analytics.example.com/matomo`) **or** a relative path (e.g., `/matomo`). The plugin normalizes relative values to an absolute URL using `window.location.origin`. If you see `.../mail/matomo.js` 404s, your URL was relative without a leading slash (`matomo`); use `/matomo` or the full URL.


Copy the sample config and edit values:

```bash
cp plugins/matomo_analytics/config.inc.php.dist plugins/matomo_analytics/config.inc.php
```

Open `plugins/matomo_analytics/config.inc.php` and set:

```php
// Matomo base URL (no trailing slash)
$config['matomo_analytics_url'] = 'https://analytics.example.com';

// Matomo site ID (integer)
$config['matomo_analytics_site_id'] = 1;

// Respect browser Do Not Track header
$config['matomo_analytics_respect_dnt'] = true;

// Disable tracking for authenticated Roundcube users
$config['matomo_analytics_disable_for_authenticated'] = false;
```

---

## Enabling the plugin

Edit `config/config.inc.php` in your Roundcube installation and add the plugin:

```php
$config['plugins'][] = 'matomo_analytics';
```

Clear Roundcube caches if necessary:

```bash
bin/cleancache.sh
```

---

## How it works

The plugin hooks into Roundcube's `render_page` event and injects the official Matomo loader:

```js
var _paq = window._paq = window._paq || [];
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);

(function() {
  var u = '<MATOMO_URL>/';
  _paq.push(['setTrackerUrl', u+'matomo.php']);
  _paq.push(['setSiteId', '<SITE_ID>']);
  var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
  g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
})();
```

When `respect_dnt` is enabled, the plugin checks the browser's DNT flags and skips injection if enabled. When `disable_for_authenticated` is `true`, no tracking is injected for logged-in users.

---

## Debugging

Set options in `plugins/matomo_analytics/config.inc.php`:

```php
$config['matomo_analytics_debug_enabled'] = true;          // write debug entries to Roundcube logs
$config['matomo_analytics_debug_html_comments'] = true;    // add HTML comment after injection
$config['matomo_analytics_debug_log_channel'] = 'matomo_analytics'; // optional custom log channel
```

View logs at `logs/matomo_analytics` (or your custom channel). Entries are JSON-encoded with event tags like
`init`, `render_page:start`, `render_page:skip:*`, `render_page:injected`, `render_page:error`.

### Smoke test
1. Enable the plugin and set `debug_enabled = true`.
2. Load Roundcube UI and check:  
   - **View Source:** find the Matomo snippet, and optionally the `<!-- matomo_analytics: injected tracking snippet -->` comment.  
   - **logs/matomo_analytics:** verify entries for injection and config.
3. Toggle `disable_for_authenticated` and `respect_dnt` to verify skip paths.

## Debugging & Verification

Enable debug in `plugins/matomo_analytics/config.inc.php`:

```php
$config['matomo_analytics_debug_enabled'] = true;
$config['matomo_analytics_debug_html_comments'] = true;
$config['matomo_analytics_debug_console'] = true;
```

Open the Roundcube UI:
- **View Source** → Confirm the Matomo snippet and optional `<!-- matomo_analytics: injected tracking snippet -->`.
- **Browser Console** → Look for `[matomo_analytics] injecting` info line.
- **Network tab** → Verify `matomo.js` loads **200** and `matomo.php` requests show **200/204** with `idsite=<your id>`.
- **Roundcube logs** (`logs/matomo_analytics`) → JSON events for injection/skip/errors.

### Common blockers
- **Content-Security-Policy** headers must allow `script-src` from your Matomo origin and `img-src` for the beacon.
- **Ad/tracking blockers** can block `matomo.js` or `matomo.php`. Test in a private window with blockers disabled.
- **Mixed Content**: Roundcube must be served over HTTPS if Matomo URL is HTTPS (and vice versa). Avoid protocol mismatches.
- **Wrong Site ID / URL**: Confirm the `Site ID` matches exactly in your Matomo UI and that the URL points to the Matomo root.

### Extras
- Cross-domain/subdomain tracking:
  ```php
  $config['matomo_analytics_cookie_domain'] = '.example.com';
  $config['matomo_analytics_domains'] = ['*.example.com'];
  ```
- Time-on-page accuracy:
  ```php
  $config['matomo_analytics_enable_heartbeat'] = true;
  ```
- JS-blocked environments: enable `<noscript>` beacon:
  ```php
  $config['matomo_analytics_fallback_img_beacon'] = true;
  ```

## Requirements

- PHP >= 7.4
- Roundcube >= 1.4
- A running Matomo server (URL + Site ID)

---

## Uninstall

Composer:

```bash
composer remove texxasrulez/roundcube-matomo-analytics
```

Manual: delete the `plugins/matomo_analytics/` directory.

---

## License

MIT
