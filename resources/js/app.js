import './bootstrap';

const root = document.documentElement;
const sidebar = document.getElementById('gp-sidebar');
const overlay = document.getElementById('gp-overlay');
const collapseBtn = document.getElementById('gp-sidebar-collapse');
const mobileOpenBtn = document.getElementById('gp-sidebar-open');
const mobileCloseBtn = document.getElementById('gp-sidebar-close');
const navFilter = document.getElementById('gp-nav-filter');
const cmd = document.getElementById('gp-cmd');
const cmdInput = document.getElementById('gp-cmd-input');
const cmdResults = document.getElementById('gp-cmd-results');
const globalSearch = document.getElementById('gp-global-search');

const FAV_KEY = 'gp-favorites';
const THEME_KEY = 'gp-theme';
const DENSITY_KEY = 'gp-density';
const DASH_KEY = 'gp-dashboard-layout';
const TABLE_PREFS_KEY = 'gp-table-prefs';

function uxConfig() {
    try {
        const el = document.getElementById('gp-ux-config');
        return el ? JSON.parse(el.textContent) : {};
    } catch {
        return {};
    }
}

const config = uxConfig();

/* ---------- Theme (light ↔ dark, LocalStorage) ---------- */
const THEME_USER_KEY = 'gp-theme-user';

function resolveDark(mode) {
    return mode === 'dark';
}

function applySidebarTone(themeMode) {
    const style = root.dataset.sidebarStyle || 'auto';
    const dark = style === 'dark' || (style !== 'light' && themeMode === 'dark');
    root.classList.toggle('gp-sidebar-dark', dark);
    root.classList.toggle('gp-sidebar-light', !dark);
}

function applyTheme(mode, { userChoice = false } = {}) {
    const m = mode === 'dark' ? 'dark' : 'light';
    localStorage.setItem(THEME_KEY, m);
    if (userChoice) {
        localStorage.setItem(THEME_USER_KEY, '1');
    }
    root.dataset.theme = m;
    root.classList.toggle('dark', resolveDark(m));
    applySidebarTone(m);
    document.querySelectorAll('[data-theme-icon]').forEach((el) => {
        // Show the icon of the *current* mode (sun = light, moon = dark)
        el.classList.toggle('hidden', el.getAttribute('data-theme-icon') !== m);
    });
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-label', m === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre');
        btn.title = m === 'dark' ? 'Mode sombre — cliquer pour le clair' : 'Mode clair — cliquer pour le sombre';
    });
}

const savedTheme = localStorage.getItem(THEME_KEY);
applyTheme(savedTheme === 'dark' || savedTheme === 'light' ? savedTheme : 'light');

document.querySelectorAll('[data-theme-toggle], #gp-theme-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
        const current = localStorage.getItem(THEME_KEY) === 'dark' ? 'dark' : 'light';
        applyTheme(current === 'light' ? 'dark' : 'light', { userChoice: true });
    });
});

// Expose for layouts that mount toggle dynamically
window.gpApplyTheme = applyTheme;
window.gpToggleTheme = () => {
    const current = localStorage.getItem(THEME_KEY) === 'dark' ? 'dark' : 'light';
    applyTheme(current === 'light' ? 'dark' : 'light', { userChoice: true });
};

/* ---------- Density ---------- */
function applyDensity(density) {
    const d = density || localStorage.getItem(DENSITY_KEY) || 'comfortable';
    localStorage.setItem(DENSITY_KEY, d);
    root.classList.remove('gp-density-compact', 'gp-density-comfortable', 'gp-density-spacious');
    root.classList.add(`gp-density-${d}`);
    document.querySelectorAll('[data-density]').forEach((btn) => {
        btn.classList.toggle('is-active', btn.getAttribute('data-density') === d);
    });
}

applyDensity();
document.querySelectorAll('[data-density]').forEach((btn) => {
    btn.addEventListener('click', () => applyDensity(btn.getAttribute('data-density')));
});

/* ---------- Toasts ---------- */
function ensureToastHost() {
    let host = document.getElementById('gp-toasts');
    if (!host) {
        host = document.createElement('div');
        host.id = 'gp-toasts';
        host.className = 'gp-toasts';
        host.setAttribute('aria-live', 'polite');
        document.body.appendChild(host);
    }
    return host;
}

window.gpToast = function gpToast(message, type = 'info', ttl = 4200) {
    const host = ensureToastHost();
    const el = document.createElement('div');
    el.className = `gp-toast gp-toast-${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `
        <div class="gp-toast-body">
            <p class="gp-toast-title">${typeLabel(type)}</p>
            <p class="gp-toast-msg">${escapeHtml(message)}</p>
        </div>
        <button type="button" class="gp-toast-close" aria-label="Fermer">×</button>
    `;
    host.appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-in'));
    const close = () => {
        el.classList.remove('is-in');
        el.classList.add('is-out');
        setTimeout(() => el.remove(), 200);
    };
    el.querySelector('.gp-toast-close')?.addEventListener('click', close);
    if (ttl > 0) setTimeout(close, ttl);
};

function typeLabel(type) {
    return { success: 'Succès', error: 'Erreur', warning: 'Avertissement', info: 'Information' }[type] || 'Info';
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function autoHideFlash(el, ttl = 2200) {
    if (!el || el.dataset.gpFlashBound === '1') return;
    el.dataset.gpFlashBound = '1';
    window.setTimeout(() => {
        el.classList.add('is-leaving');
        window.setTimeout(() => el.remove(), 280);
    }, ttl);
}

const seenFlash = new Set();
document.querySelectorAll('[data-gp-flash], .gp-flash-success, .gp-flash-info, .gp-flash-warning, .mb-4.border-emerald-200.bg-emerald-50').forEach((flash) => {
    const type = flash.getAttribute('data-gp-flash')
        || (flash.classList.contains('gp-flash-error') ? 'error' : null)
        || (flash.classList.contains('gp-flash-warning') ? 'warning' : null)
        || (flash.classList.contains('gp-flash-info') ? 'info' : 'success');
    if (type === 'error') return;
    if (flash.querySelector('form, input, table, ul.list-disc')) return;

    const key = `${type}:${(flash.textContent || '').trim()}`;
    if (seenFlash.has(key)) {
        flash.remove();
        return;
    }
    seenFlash.add(key);
    autoHideFlash(flash, type === 'success' ? 2200 : 3500);
});

/* ---------- Sidebar ---------- */
function openMobileSidebar() {
    sidebar?.classList.add('is-open');
    overlay?.classList.add('open');
    document.body.classList.add('overflow-hidden');
}

function closeMobileSidebar() {
    sidebar?.classList.remove('is-open');
    overlay?.classList.remove('open');
    document.body.classList.remove('overflow-hidden');
}

mobileOpenBtn?.addEventListener('click', openMobileSidebar);
mobileCloseBtn?.addEventListener('click', closeMobileSidebar);
overlay?.addEventListener('click', closeMobileSidebar);

collapseBtn?.addEventListener('click', () => {
    sidebar?.classList.toggle('is-collapsed');
    localStorage.setItem('gp-sidebar-collapsed', sidebar?.classList.contains('is-collapsed') ? '1' : '0');
});

if (localStorage.getItem('gp-sidebar-collapsed') === '1') {
    sidebar?.classList.add('is-collapsed');
}

/* ---------- Dropdowns ---------- */
document.querySelectorAll('[data-dropdown-trigger]').forEach((trigger) => {
    const menu = document.getElementById(trigger.getAttribute('data-dropdown-trigger'));
    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const parentId = trigger.getAttribute('data-close-parent');
        if (parentId) {
            document.getElementById(parentId)?.classList.remove('open');
        }
        document.querySelectorAll('.gp-dropdown.open').forEach((openMenu) => {
            if (openMenu !== menu) openMenu.classList.remove('open');
        });
        const open = menu?.classList.toggle('open');
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});

document.addEventListener('click', () => {
    document.querySelectorAll('.gp-dropdown.open').forEach((menu) => menu.classList.remove('open'));
    document.querySelectorAll('[data-dropdown-trigger]').forEach((t) => t.setAttribute('aria-expanded', 'false'));
});

/* ---------- Logout confirmation ---------- */
(() => {
    const modal = document.getElementById('gp-logout-modal');
    if (!modal) return;

    const open = () => {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.querySelectorAll('.gp-dropdown.open').forEach((m) => m.classList.remove('open'));
        modal.querySelector('[data-logout-cancel]')?.focus?.();
    };
    const close = () => {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-logout-open]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            open();
        });
    });
    modal.querySelectorAll('[data-logout-cancel]').forEach((btn) => {
        btn.addEventListener('click', close);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) close();
    });
})();

/* ---------- Session expiry (fetch 401/419) ---------- */
const _origFetch = window.fetch?.bind(window);
if (_origFetch) {
    window.fetch = async (...args) => {
        const res = await _origFetch(...args);
        if (res.status === 401 || res.status === 419) {
            try {
                const data = await res.clone().json();
                if (data?.code === 'session_expired' || data?.code === 'csrf_expired' || res.status === 419) {
                    window.location.href = '/login?expired=1';
                }
            } catch {
                if (res.status === 419) window.location.href = '/login?expired=1';
            }
        }
        if (res.status === 423) {
            window.location.href = '/lock';
        }
        return res;
    };
}

/* ---------- Nav filter ---------- */
navFilter?.addEventListener('input', () => {
    const q = navFilter.value.trim().toLowerCase();
    document.querySelectorAll('[data-nav-item]').forEach((link) => {
        const label = (link.getAttribute('data-nav-label') || '').toLowerCase();
        link.classList.toggle('is-hidden', !(!q || label.includes(q)));
    });
    document.querySelectorAll('[data-nav-group]').forEach((group) => {
        const visible = [...group.querySelectorAll('[data-nav-item]')].some((l) => !l.classList.contains('is-hidden'));
        group.style.display = visible || !q ? '' : 'none';
    });
});

/* ---------- Favorites ---------- */
function getFavorites() {
    try {
        return JSON.parse(localStorage.getItem(FAV_KEY) || '[]');
    } catch {
        return [];
    }
}

function setFavorites(list) {
    localStorage.setItem(FAV_KEY, JSON.stringify(list.slice(0, 20)));
    renderFavorites();
    syncFavStars();
    syncPagePin();
}

function syncFavStars() {
    const favs = getFavorites();
    document.querySelectorAll('[data-nav-item]').forEach((link) => {
        const href = link.getAttribute('data-nav-href');
        link.querySelector('[data-fav-toggle]')?.classList.toggle('is-active', favs.some((f) => f.href === href));
    });
}

function renderFavorites() {
    const wrap = document.getElementById('gp-favorites');
    const list = document.getElementById('gp-favorites-list');
    if (!wrap || !list) return;
    const favs = getFavorites();
    wrap.classList.toggle('hidden', favs.length === 0);
    list.innerHTML = favs.map((f) => `
        <a href="${f.href}" class="gp-nav-link ${location.pathname === new URL(f.href, location.origin).pathname ? 'gp-nav-link-active' : ''}" title="${escapeHtml(f.label)}">
            <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            <span>${escapeHtml(f.label)}</span>
        </a>
    `).join('');
}

function toggleFavoriteHref(href, label, type = 'page') {
    let favs = getFavorites();
    if (favs.some((f) => f.href === href)) {
        favs = favs.filter((f) => f.href !== href);
        window.gpToast('Retiré des favoris', 'info', 2200);
    } else {
        favs.push({ href, label, type });
        window.gpToast('Ajouté aux favoris', 'success', 2200);
    }
    setFavorites(favs);
}

function toggleFavorite(el) {
    const link = el.closest('[data-nav-item]');
    if (!link) return;
    toggleFavoriteHref(link.getAttribute('data-nav-href'), link.getAttribute('data-nav-label'), 'module');
}

document.querySelectorAll('[data-fav-toggle]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleFavorite(btn);
    });
    btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            toggleFavorite(btn);
        }
    });
});

function syncPagePin() {
    const pin = document.getElementById('gp-pin-page');
    if (!pin) return;
    const href = pin.getAttribute('data-href') || location.pathname;
    const active = getFavorites().some((f) => f.href === href || new URL(f.href, location.origin).pathname === location.pathname);
    pin.classList.toggle('is-pinned', active);
    pin.setAttribute('aria-pressed', active ? 'true' : 'false');
    pin.title = active ? 'Retirer des favoris' : 'Épingler cette page';
}

document.getElementById('gp-pin-page')?.addEventListener('click', () => {
    const pin = document.getElementById('gp-pin-page');
    const href = pin.getAttribute('data-href') || location.href;
    const label = pin.getAttribute('data-label') || document.title.replace(' — GreenPOS', '');
    toggleFavoriteHref(href, label, pin.getAttribute('data-type') || 'page');
});

renderFavorites();
syncFavStars();
syncPagePin();

/* ---------- Command palette + universal search ---------- */
function navItems() {
    return [...document.querySelectorAll('[data-nav-item]')].map((el) => ({
        kind: 'nav',
        label: el.getAttribute('data-nav-label'),
        subtitle: 'Navigation',
        href: el.getAttribute('data-nav-href'),
        icon: (el.getAttribute('data-nav-label') || '?').charAt(0),
    }));
}

function actionItems() {
    return (config.actions || []).map((a) => ({
        kind: 'action',
        label: a.label,
        subtitle: a.group || 'Action',
        href: a.href,
        icon: a.icon || '⚡',
        keywords: a.keywords || '',
    }));
}

let cmdIndex = 0;
let searchTimer = null;
let lastResults = [];

function openCmd(initial = '') {
    if (!cmd) return;
    cmd.hidden = false;
    cmd.classList.add('open');
    cmdInput.value = initial;
    renderCmdResults(initial);
    cmdInput.focus();
    if (initial.length >= 2) fetchSearch(initial);
}

function closeCmd() {
    if (!cmd) return;
    cmd.classList.remove('open');
    cmd.hidden = true;
    clearTimeout(searchTimer);
}

function renderCmdList(items) {
    lastResults = items;
    cmdIndex = 0;
    if (!items.length) {
        cmdResults.innerHTML = '<p class="px-4 py-8 text-center text-sm text-gp-muted">Aucun résultat</p>';
        return;
    }
    let html = '';
    let currentGroup = '';
    items.forEach((i, idx) => {
        const group = i.subtitle || i.type_label || 'Résultats';
        if (group !== currentGroup) {
            currentGroup = group;
            html += `<p class="gp-cmd-group">${escapeHtml(group)}</p>`;
        }
        html += `
            <a href="${i.href || i.url}" class="gp-cmd-item ${idx === 0 ? 'is-active' : ''}" data-cmd-idx="${idx}" role="option">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gp-primary-soft text-gp-primary text-xs font-bold">${escapeHtml(i.icon || (i.type_label || '?').charAt(0))}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium text-gp-text">${escapeHtml(i.title || i.label)}</span>
                    <span class="block truncate text-[11px] text-gp-muted">${escapeHtml(i.subtitle || i.type_label || '')}</span>
                </span>
            </a>`;
    });
    cmdResults.innerHTML = html;
}

function renderCmdResults(query) {
    if (!cmdResults) return;
    const q = query.trim().toLowerCase();
    const actions = actionItems().filter((i) => {
        if (!q) return true;
        return i.label.toLowerCase().includes(q) || (i.keywords || '').toLowerCase().includes(q);
    });
    const nav = navItems().filter((i) => !q || i.label.toLowerCase().includes(q));
    const combined = [...actions.slice(0, q ? 8 : 6), ...nav.slice(0, q ? 8 : 10)];
    renderCmdList(combined);
}

async function fetchSearch(query) {
    if (!config.searchUrl || query.trim().length < 2) return;
    try {
        cmdResults.insertAdjacentHTML('afterbegin', '<p class="px-4 py-2 text-[11px] text-gp-muted" id="gp-cmd-loading">Recherche…</p>');
        const url = new URL(config.searchUrl, location.origin);
        url.searchParams.set('q', query.trim());
        const res = await window.axios.get(url.toString());
        document.getElementById('gp-cmd-loading')?.remove();
        const remote = (res.data.results || []).map((r) => ({
            kind: 'record',
            label: r.title,
            title: r.title,
            subtitle: `${r.type_label}${r.subtitle ? ' · ' + r.subtitle : ''}`,
            type_label: r.type_label,
            href: r.url,
            icon: r.icon || r.type_label?.charAt(0) || '•',
        }));
        const q = query.trim().toLowerCase();
        const actions = actionItems().filter((i) => i.label.toLowerCase().includes(q) || (i.keywords || '').toLowerCase().includes(q));
        const nav = navItems().filter((i) => i.label.toLowerCase().includes(q));
        renderCmdList([...actions.slice(0, 4), ...remote, ...nav.slice(0, 4)]);
    } catch {
        document.getElementById('gp-cmd-loading')?.remove();
        window.gpToast('Recherche indisponible', 'warning', 2500);
    }
}

cmdInput?.addEventListener('input', () => {
    const q = cmdInput.value;
    renderCmdResults(q);
    clearTimeout(searchTimer);
    if (q.trim().length >= 2) {
        searchTimer = setTimeout(() => fetchSearch(q), 220);
    }
});

cmdInput?.addEventListener('keydown', (e) => {
    const items = [...cmdResults.querySelectorAll('.gp-cmd-item')];
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        cmdIndex = (cmdIndex + 1) % items.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        cmdIndex = (cmdIndex - 1 + items.length) % items.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        items[cmdIndex]?.click();
        return;
    } else {
        return;
    }
    items.forEach((el, i) => el.classList.toggle('is-active', i === cmdIndex));
    items[cmdIndex]?.scrollIntoView({ block: 'nearest' });
});

globalSearch?.addEventListener('click', () => openCmd());
globalSearch?.addEventListener('focus', () => openCmd());
cmd?.addEventListener('click', (e) => {
    if (e.target === cmd) closeCmd();
});

/* ---------- Help / shortcuts panels ---------- */
function openPanel(id) {
    const panel = document.getElementById(id);
    if (!panel) return;
    panel.hidden = false;
    panel.classList.add('open');
}

function closePanel(id) {
    const panel = document.getElementById(id);
    if (!panel) return;
    panel.classList.remove('open');
    panel.hidden = true;
}

function closeAllPanels() {
    closeCmd();
    closePanel('gp-help');
    closePanel('gp-shortcuts');
    document.querySelectorAll('.gp-dropdown.open').forEach((menu) => menu.classList.remove('open'));
    closeMobileSidebar();
}

document.getElementById('gp-help-open')?.addEventListener('click', () => openPanel('gp-help'));
document.getElementById('gp-shortcuts-open')?.addEventListener('click', () => openPanel('gp-shortcuts'));
document.querySelectorAll('[data-gp-close]').forEach((btn) => {
    btn.addEventListener('click', () => closePanel(btn.getAttribute('data-gp-close')));
});

document.querySelectorAll('.gp-help-backdrop').forEach((bd) => {
    bd.addEventListener('click', () => {
        const panel = bd.closest('.gp-help-panel');
        if (panel?.id) closePanel(panel.id);
    });
});

/* ---------- Keyboard shortcuts ---------- */
function isTypingTarget(el) {
    if (!el) return false;
    const tag = el.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
}

document.addEventListener('keydown', (event) => {
    const meta = event.metaKey || event.ctrlKey;
    const key = event.key.toLowerCase();

    if (meta && key === 'k') {
        event.preventDefault();
        if (cmd?.classList.contains('open')) closeCmd();
        else openCmd();
        return;
    }

    if (event.key === 'Escape') {
        closeAllPanels();
        return;
    }

    if (meta && key === 'n' && !isTypingTarget(event.target)) {
        const createProduct = (config.actions || []).find((a) => a.id === 'create-product');
        if (createProduct?.href) {
            event.preventDefault();
            location.href = createProduct.href;
        }
        return;
    }

    if (meta && key === 's') {
        const form = document.querySelector('form[data-gp-save], main form:not([method="get"])');
        if (form && !cmd?.classList.contains('open')) {
            event.preventDefault();
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
            window.gpToast('Enregistrement…', 'info', 1800);
        }
        return;
    }

    if (meta && key === 'p' && !isTypingTarget(event.target)) {
        const printLink = document.querySelector('a[href*="print"], a[data-gp-print], button[data-gp-print]');
        if (printLink) {
            event.preventDefault();
            printLink.click();
        } else {
            event.preventDefault();
            window.print();
        }
        return;
    }

    if (!meta && event.key === '?' && !isTypingTarget(event.target)) {
        event.preventDefault();
        openPanel('gp-shortcuts');
    }
});

/* ---------- Tooltips / contextual help ---------- */
document.querySelectorAll('[data-gp-tip]').forEach((el) => {
    el.classList.add('gp-has-tip');
    el.setAttribute('tabindex', el.getAttribute('tabindex') || '0');
});

/* ---------- Customizable tables ---------- */
function tablePrefs() {
    try {
        return JSON.parse(localStorage.getItem(TABLE_PREFS_KEY) || '{}');
    } catch {
        return {};
    }
}

function saveTablePrefs(all) {
    localStorage.setItem(TABLE_PREFS_KEY, JSON.stringify(all));
}

document.querySelectorAll('[data-gp-table]').forEach((table) => {
    const id = table.getAttribute('data-gp-table');
    if (!id) return;
    const prefs = tablePrefs()[id] || {};
    const headers = [...table.querySelectorAll('thead th[data-col]')];
    const cols = headers.map((th) => th.getAttribute('data-col'));

    // Apply hidden columns
    (prefs.hidden || []).forEach((col) => {
        table.querySelectorAll(`[data-col="${col}"]`).forEach((cell) => {
            cell.style.display = 'none';
        });
    });

    // Apply order if stored
    if (Array.isArray(prefs.order) && prefs.order.length) {
        const theadRow = table.querySelector('thead tr');
        const bodyRows = [...table.querySelectorAll('tbody tr')];
        prefs.order.forEach((col) => {
            const th = theadRow?.querySelector(`th[data-col="${col}"]`);
            if (th) theadRow.appendChild(th);
            bodyRows.forEach((tr) => {
                const td = tr.querySelector(`td[data-col="${col}"]`);
                if (td) tr.appendChild(td);
            });
        });
    }

    const toolbar = document.querySelector(`[data-gp-table-cols="${id}"]`);
    if (toolbar) {
        toolbar.innerHTML = cols.map((col) => {
            const hidden = (prefs.hidden || []).includes(col);
            return `<label class="inline-flex items-center gap-1.5 text-xs text-gp-muted"><input type="checkbox" data-col-toggle="${col}" ${hidden ? '' : 'checked'}> ${col}</label>`;
        }).join('');
        toolbar.querySelectorAll('[data-col-toggle]').forEach((cb) => {
            cb.addEventListener('change', () => {
                const col = cb.getAttribute('data-col-toggle');
                const all = tablePrefs();
                const current = all[id] || { hidden: [], order: cols };
                if (cb.checked) {
                    current.hidden = (current.hidden || []).filter((c) => c !== col);
                } else {
                    current.hidden = [...new Set([...(current.hidden || []), col])];
                }
                all[id] = current;
                saveTablePrefs(all);
                table.querySelectorAll(`[data-col="${col}"]`).forEach((cell) => {
                    cell.style.display = cb.checked ? '' : 'none';
                });
                window.gpToast('Préférences tableau enregistrées', 'success', 1800);
            });
        });
    }

    // Drag reorder headers
    headers.forEach((th) => {
        th.setAttribute('draggable', 'true');
        th.classList.add('gp-col-draggable');
        th.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', th.getAttribute('data-col'));
            th.classList.add('is-dragging-col');
        });
        th.addEventListener('dragend', () => th.classList.remove('is-dragging-col'));
        th.addEventListener('dragover', (e) => e.preventDefault());
        th.addEventListener('drop', (e) => {
            e.preventDefault();
            const from = e.dataTransfer.getData('text/plain');
            const to = th.getAttribute('data-col');
            if (!from || from === to) return;
            const theadRow = table.querySelector('thead tr');
            const fromTh = theadRow.querySelector(`th[data-col="${from}"]`);
            if (!fromTh) return;
            theadRow.insertBefore(fromTh, th);
            table.querySelectorAll('tbody tr').forEach((tr) => {
                const fromTd = tr.querySelector(`td[data-col="${from}"]`);
                const toTd = tr.querySelector(`td[data-col="${to}"]`);
                if (fromTd && toTd) tr.insertBefore(fromTd, toTd);
            });
            const order = [...theadRow.querySelectorAll('th[data-col]')].map((h) => h.getAttribute('data-col'));
            const all = tablePrefs();
            all[id] = { ...(all[id] || {}), order };
            saveTablePrefs(all);
            window.gpToast('Colonnes réorganisées', 'success', 1800);
        });
    });
});

/* ---------- Dashboard widgets layout ---------- */
function dashLayout() {
    try {
        return JSON.parse(localStorage.getItem(DASH_KEY) || '{}');
    } catch {
        return {};
    }
}

function applyDashLayout() {
    const layout = dashLayout();
    const board = document.querySelector('[data-gp-dashboard]');
    if (!board) return;

    Object.entries(layout.hidden || {}).forEach(([id, hidden]) => {
        const w = board.querySelector(`[data-gp-widget="${id}"]`);
        if (w && !w.hasAttribute('data-gp-locked')) w.classList.toggle('is-widget-hidden', !!hidden);
    });
}

function persistDashOrder() {
    const board = document.querySelector('[data-gp-dashboard]');
    if (!board) return;
    const layout = dashLayout();
    layout.order = [...board.querySelectorAll('[data-gp-widget]')].map((w) => w.getAttribute('data-gp-widget'));
    localStorage.setItem(DASH_KEY, JSON.stringify(layout));
}

applyDashLayout();

let dragWidget = null;
document.querySelectorAll('[data-gp-dashboard] [data-gp-widget]').forEach((widget) => {
    if (widget.hasAttribute('data-gp-locked')) return;
    widget.setAttribute('draggable', 'true');
    widget.addEventListener('dragstart', (e) => {
        if (!document.querySelector('[data-gp-dashboard]')?.classList.contains('is-customizing')) {
            e.preventDefault();
            return;
        }
        dragWidget = widget;
        widget.classList.add('is-dragging');
    });
    widget.addEventListener('dragend', () => {
        widget.classList.remove('is-dragging');
        dragWidget = null;
        persistDashOrder();
        window.gpToast('Disposition enregistrée', 'success', 1600);
    });
    widget.addEventListener('dragover', (e) => {
        if (!document.querySelector('[data-gp-dashboard]')?.classList.contains('is-customizing')) return;
        e.preventDefault();
    });
    widget.addEventListener('drop', (e) => {
        e.preventDefault();
        if (!dragWidget || dragWidget === widget) return;
        if (dragWidget.parentElement !== widget.parentElement) {
            window.gpToast('Déplacez les widgets dans la même rangée', 'info', 2200);
            return;
        }
        const parent = widget.parentElement;
        const nodes = [...parent.querySelectorAll(':scope > [data-gp-widget]')];
        const from = nodes.indexOf(dragWidget);
        const to = nodes.indexOf(widget);
        if (from < 0 || to < 0) return;
        if (from < to) widget.after(dragWidget);
        else widget.before(dragWidget);
    });
});

document.getElementById('gp-dash-customize')?.addEventListener('click', () => {
    const board = document.querySelector('[data-gp-dashboard]');
    board?.classList.toggle('is-customizing');
    const customizing = board?.classList.contains('is-customizing');
    document.getElementById('gp-dash-customize')?.classList.toggle('is-active', customizing);
    window.gpToast(customizing ? 'Mode personnalisation activé' : 'Mode personnalisation désactivé', 'info', 2000);
});

document.querySelectorAll('[data-gp-widget-hide]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-gp-widget-hide');
        const w = document.querySelector(`[data-gp-widget="${id}"]`);
        if (!w) return;
        w.classList.add('is-widget-hidden');
        const layout = dashLayout();
        layout.hidden = { ...(layout.hidden || {}), [id]: true };
        localStorage.setItem(DASH_KEY, JSON.stringify(layout));
        window.gpToast('Widget masqué', 'info', 1800);
    });
});

document.getElementById('gp-dash-reset')?.addEventListener('click', () => {
    localStorage.removeItem(DASH_KEY);
    location.reload();
});

/* ---------- Invalid form fields ---------- */
document.querySelectorAll('.gp-input, .gp-select, .gp-textarea').forEach((el) => {
    if (el.classList.contains('border-rose-500') || el.getAttribute('aria-invalid') === 'true') {
        el.classList.add('is-invalid');
    }
});
