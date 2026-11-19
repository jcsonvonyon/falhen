<?php
/**
 * Unified Analytics & Tagging Engine
 * GA4 Measurement ID: G-LJ91C0YXCH
 * Brand: Falhen Productions
 */

$gaId = 'G-LJ91C0YXCH';
?>
<!-- Google tag (gtag.js) - Asynchronously loaded & optimized -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($gaId); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?php echo htmlspecialchars($gaId); ?>', {
    'send_page_view': true,
    'anonymize_ip': true,
    'cookie_flags': 'SameSite=None;Secure'
  });

  // Client-side Custom Event Helper
  window.falhenTrack = function(eventName, eventParams) {
    if (typeof gtag === 'function') {
      gtag('event', eventName, eventParams || {});
    }
  };
</script>
