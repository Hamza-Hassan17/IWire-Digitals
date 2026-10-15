/* iWire Control Room — global client behaviour (Alpine.js stores & components) */
(function () {
  const ls = {
    get(k, d) { try { const v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
  };
  const fmt = x => { x = Math.max(0, Math.floor(x)); return x >= 3600 ? Math.floor(x / 3600) + ':' + String(Math.floor(x % 3600 / 60)).padStart(2, '0') + ':' + String(x % 60).padStart(2, '0') : Math.floor(x / 60) + ':' + String(x % 60).padStart(2, '0'); };
  window.iwFmt = fmt;
  window.iwCsrf = () => document.querySelector('meta[name=csrf-token]').content;

  /** Submit a hidden POST form (used by keyboard shortcuts and JS-driven actions). */
  window.iwPost = (url, data = {}, method = 'POST') => {
    const f = document.createElement('form');
    f.method = 'POST'; f.action = url; f.style.display = 'none';
    const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
    add('_token', window.iwCsrf());
    if (method !== 'POST') add('_method', method);
    Object.entries(data).forEach(([k, v]) => Array.isArray(v) ? v.forEach(x => add(k + '[]', x)) : add(k, v));
    document.body.appendChild(f); f.submit();
  };

  document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
      on: false, msg: '', kind: 'ok', _t: null,
      show(msg, kind = 'ok') { clearTimeout(this._t); this.msg = msg; this.kind = kind; this.on = true; this._t = setTimeout(() => this.on = false, 3200); },
      get icon() { return { ok: 'check', bad: 'close', info: 'info' }[this.kind] || 'check'; },
      get fg() { return { ok: '#5FD99A', bad: '#F38A8A', info: '#6CC4FF' }[this.kind] || '#5FD99A'; },
      get bg() { return { ok: 'rgba(52,199,123,.18)', bad: 'rgba(240,82,82,.18)', info: 'rgba(10,155,255,.18)' }[this.kind] || 'rgba(52,199,123,.18)'; },
    });

    Alpine.store('player', {
      item: ls.get('iw.player', null), playing: false, pos: ls.get('iw.pos', 0.34),
      init() { setInterval(() => { if (this.playing && this.item) { this.pos = this.pos >= 1 ? 0 : this.pos + 1 / Math.max(30, this.item.secs || 200); ls.set('iw.pos', this.pos); } }, 1000); },
      play(item) { this.item = item; this.playing = true; this.pos = 0; ls.set('iw.player', item); ls.set('iw.pos', 0); },
      toggle() { this.playing = !this.playing; },
      close() { this.item = null; this.playing = false; ls.set('iw.player', null); },
      skip(d) { if (!this.item) return; this.pos = Math.min(1, Math.max(0, this.pos + d / (this.item.secs || 200))); ls.set('iw.pos', this.pos); },
      seek(e) { const r = e.currentTarget.getBoundingClientRect(); this.pos = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width)); ls.set('iw.pos', this.pos); },
      get cur() { return fmt((this.item?.secs || 200) * this.pos); },
      get total() { return fmt(this.item?.secs || 200); },
      get thumbW() { return this.item?.type === 'Films' ? '84px' : '52px'; },
      get tag() { return this.item ? (this.item.status === 'Pending' ? 'In review' : this.item.status || 'Live') : ''; },
    });

    Alpine.data('shell', () => ({
      collapsed: ls.get('iw.collapsed', false), notif: false, menu: false,
      palette: false, q: '', results: [], hi: 0, _st: null,
      toggleSide() { this.collapsed = !this.collapsed; ls.set('iw.collapsed', this.collapsed); },
      openPalette() { this.palette = true; this.q = ''; this.search(); this.$nextTick(() => this.$refs.pq && this.$refs.pq.focus()); },
      search() {
        clearTimeout(this._st);
        this._st = setTimeout(() => fetch(window.IW_SEARCH + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } })
          .then(r => r.json()).then(d => { this.results = d; this.hi = 0; }), 90);
      },
      get flat() { return this.results.flatMap(g => g.items); },
      go(item) { if (item) window.location = item.url; },
      key(e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.palette ? this.palette = false : this.openPalette(); }
        if (e.key === 'Escape') { this.palette = false; this.notif = false; this.menu = false; }
        if (this.palette && e.key === 'ArrowDown') { e.preventDefault(); this.hi = Math.min(this.flat.length - 1, this.hi + 1); }
        if (this.palette && e.key === 'ArrowUp') { e.preventDefault(); this.hi = Math.max(0, this.hi - 1); }
        if (this.palette && e.key === 'Enter') { e.preventDefault(); this.go(this.flat[this.hi]); }
      },
    }));
  });
})();
