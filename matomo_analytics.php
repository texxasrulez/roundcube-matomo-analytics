<?php
/**
 * Matomo Analytics integration for Roundcube
 * Robust absolute-URL normalization for Matomo base URL.
 * @version 1.3.0
 * @license MIT
 */
class matomo_analytics extends rcube_plugin
{
    public $task = '?(?!logout).*';
    private $cfg = array();
    private $log_channel = 'matomo_analytics';

    function init()
    {
        $this->load_config();
        $rcmail = rcmail::get_instance();

        $this->cfg['url'] = rtrim((string)$rcmail->config->get('matomo_analytics_url', ''), '/');
        $this->cfg['site_id'] = (int)$rcmail->config->get('matomo_analytics_site_id', 0);
        $this->cfg['respect_dnt'] = (bool)$rcmail->config->get('matomo_analytics_respect_dnt', true);
        $this->cfg['disable_for_authenticated'] = (bool)$rcmail->config->get('matomo_analytics_disable_for_authenticated', false);
        $this->cfg['debug_enabled'] = (bool)$rcmail->config->get('matomo_analytics_debug_enabled', false);
        $this->cfg['debug_html_comments'] = (bool)$rcmail->config->get('matomo_analytics_debug_html_comments', false);
        $this->cfg['debug_console'] = (bool)$rcmail->config->get('matomo_analytics_debug_console', false);
        $this->cfg['debug_log_channel'] = (string)$rcmail->config->get('matomo_analytics_debug_log_channel', $this->log_channel);
        if (!empty($this->cfg['debug_log_channel'])) $this->log_channel = $this->cfg['debug_log_channel'];
        $this->add_hook('render_page', array($this, 'on_render_page'));
    }

    public function on_render_page($p)
    {
        $rcmail = rcmail::get_instance();
        if (!$this->cfg['url'] || !$this->cfg['site_id']) {
            $this->debug('render_page:skip:missing_config', array('url'=>$this->cfg['url'], 'site_id'=>$this->cfg['site_id']));
            return $p;
        }
        if ($this->cfg['disable_for_authenticated'] && $rcmail->user && $rcmail->user->ID) {
            $this->debug('render_page:skip:authenticated_user');
            return $p;
        }
        $dnt_js = $this->cfg['respect_dnt'] ? "if (navigator.doNotTrack == '1' || window.doNotTrack == '1' || navigator.msDoNotTrack == '1') { return; }" : "";
        $console_js = $this->cfg['debug_console'] ? "try { console.info('[matomo_analytics] injecting'); } catch(e) {}" : "";

        $script = <<<HTML
<!-- Matomo -->
<script>
(function() {
  {$dnt_js}
  var _paq = window._paq = window._paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);

  // Robust absolute URL normalization. Supports '/matomo', 'matomo', or full URLs.
  var base = "{$this->cfg['url']}";
  try {
    var a = document.createElement('a');
    a.href = base;
    // If missing protocol/host, anchor will resolve relative to current path.
    // In that case, prefix with origin and ensure single slash.
    var abs = a.href;
    if (!/^https?:/i.test(base)) {
      var origin = (location.origin || (location.protocol + '//' + location.host)).replace(/\/$/, '');
      abs = origin + '/' + String(base).replace(/^\/+/, '');
    }
    base = abs.replace(/\/$/, '');
  } catch(e) {}

  var u = base + '/';
  _paq.push(['setTrackerUrl', u + 'matomo.php']);
  _paq.push(['setSiteId', '{$this->cfg['site_id']}']);
  var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
  g.async=true; g.src=u + 'matomo.js'; s.parentNode.insertBefore(g,s);

  {$console_js}
})();
</script>
<!-- End Matomo Code -->
HTML;

        if (strpos($p['content'], '</body>') !== false) {
            $p['content'] = str_replace('</body>', $script . "\n</body>", $p['content']);
        } else {
            $p['content'] .= $script;
        }
        if ($this->cfg['debug_html_comments']) {
            $p['content'] .= "\n<!-- matomo_analytics: injected tracking snippet -->\n";
        }
        $this->debug('render_page:injected');
        return $p;
    }

    private function debug($event, $context = array())
    {
        if (!$this->cfg['debug_enabled']) return;
        rcube::write_log($this->log_channel, '['.$event.'] ' . json_encode($context));
    }
}
