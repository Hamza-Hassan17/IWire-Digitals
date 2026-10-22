/* iWire Control Room — global client behaviour (Alpine.js stores & components) */
(function () {
  const ls = {
    get(k, d) { try { const v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
  };
  window.iwFmt = x => { x = Math.max(0, Math.floor(x)); return x >= 3600 ? Math.floor(x / 3600) + ':' + String(Math.floor(x % 3600 / 60)).padStart(2, '0') + ':' + String(x % 60).padStart(2, '0') : Math.floor(x / 60) + ':' + String(x % 60).padStart(2, '0'); };
  window.iwCsrf = () => document.querySelector('meta[name=csrf-token]').content;

  document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
      on: false, msg: '', kind: 'ok', _t: null,
      show(msg, kind = 'ok') { clearTimeout(this._t); this.msg = msg; this.kind = kind; this.on = true; this._t = setTimeout(() => this.on = false, 3200); },
      get icon() { return { ok: 'check', bad: 'close', info: 'info' }[this.kind] || 'check'; },
      get fg() { return { ok: '#5FD99A', bad: '#F38A8A', info: '#6CC4FF' }[this.kind] || '#5FD99A'; },
      get bg() { return { ok: 'rgba(52,199,123,.18)', bad: 'rgba(240,82,82,.18)', info: 'rgba(10,155,255,.18)' }[this.kind] || 'rgba(52,199,123,.18)'; },
    });

    Alpine.data('shell', () => ({
      collapsed: ls.get('iw.collapsed', false), notif: false, menu: false,
      toggleSide() { this.collapsed = !this.collapsed; ls.set('iw.collapsed', this.collapsed); },
    }));
  });
})();
