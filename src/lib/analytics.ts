// Privacy-first page analytics, talking only to this server.
// Changes from the old version:
// - nothing is stored on the device (no sessionStorage): sessions are derived server-side
// - no request at all when Do Not Track / Global Privacy Control is set
// - the "leave" update carries a server-issued signature, so ids can't be tampered with
// - 404 pages report themselves, replacing the GoAccess log parsing

(() => {
  const nav = navigator as Navigator & { globalPrivacyControl?: boolean };
  if (nav.globalPrivacyControl || nav.doNotTrack === '1') return;
  if (/^(localhost|127\.|\[::1\])/.test(location.hostname)) return;
  if (location.pathname.startsWith('/analytics/')) return;

  const endpoint = '/analytics/beacon.php';
  let engagedMs = 0;
  let resumedAt: number | null = null;
  let maxScroll = 0;
  let visit: { id: number; sig: string } | null = null;

  const pause = () => { if (resumedAt !== null) { engagedMs += Date.now() - resumedAt; resumedAt = null; } };
  const resume = () => { if (resumedAt === null) resumedAt = Date.now(); };
  const engagedSeconds = () => Math.round((engagedMs + (resumedAt !== null ? Date.now() - resumedAt : 0)) / 1000);

  const onScroll = () => {
    const h = document.documentElement.scrollHeight;
    const pct = h > 0 ? Math.round(((scrollY + innerHeight) / h) * 100) : 100;
    if (pct > maxScroll) maxScroll = Math.min(100, pct);
  };

  function sendView() {
    if (document.visibilityState !== 'hidden') resume();
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    const sp = new URLSearchParams(location.search);
    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'view',
        page: location.pathname,
        referrer: document.referrer,
        utm_source: sp.get('utm_source') || '',
        utm_medium: sp.get('utm_medium') || '',
        utm_campaign: sp.get('utm_campaign') || '',
        screen_w: screen.width,
        status: document.documentElement.dataset.status === '404' ? 404 : 200,
      }),
    })
      .then((r) => r.json())
      .then((d) => { if (d.id && d.sig) visit = { id: d.id, sig: d.sig }; })
      .catch(() => {});
  }

  // Sent on every hide; the server keeps the maximum, so tabbing away and back is measured correctly.
  function sendLeave() {
    pause();
    if (!visit) return;
    const body = JSON.stringify({ type: 'leave', id: visit.id, sig: visit.sig, duration: engagedSeconds(), scroll_depth: maxScroll });
    if (!navigator.sendBeacon?.(endpoint, new Blob([body], { type: 'application/json' }))) {
      fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body, keepalive: true }).catch(() => {});
    }
  }

  document.addEventListener('visibilitychange', () => (document.visibilityState === 'hidden' ? sendLeave() : resume()));
  addEventListener('pagehide', sendLeave);

  const doc = document as Document & { prerendering?: boolean };
  if (doc.prerendering) document.addEventListener('prerenderingchange', sendView, { once: true });
  else sendView();
})();
