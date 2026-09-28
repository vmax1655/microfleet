import './bootstrap';
import Chart from 'chart.js/auto';

/* ------------------------------------------------------------------
   Ledger front-end runtime
   Alpine ships with Livewire 3, so we only register behaviours here.
   Every behaviour below maps 1:1 to something expressible in Blade.
   ------------------------------------------------------------------ */

window.Chart = Chart;

Chart.defaults.font.family =
    "Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#5A6B64';
Chart.defaults.borderColor = '#E2E8E5';
Chart.defaults.plugins.legend.labels.boxWidth = 10;
Chart.defaults.plugins.legend.labels.boxHeight = 10;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.legend.labels.padding = 16;

/** ₱1,234,567.89 — mirrors App\Support\Format::peso() */
export function peso(value, decimals = 2) {
    const n = Number(value || 0);
    return (
        '₱' +
        n.toLocaleString('en-PH', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        })
    );
}

/** ₱1.24M — for compact axis ticks */
export function pesoShort(value) {
    const n = Number(value || 0);
    if (Math.abs(n) >= 1_000_000) return '₱' + (n / 1_000_000).toFixed(1) + 'M';
    if (Math.abs(n) >= 1_000) return '₱' + Math.round(n / 1_000) + 'K';
    return '₱' + n;
}

window.LedgerFormat = { peso, pesoShort };

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /* -------------------------------------------------- app shell + sidebar */
    Alpine.data('ledgerShell', (activeModule = '') => ({
        collapsed: false,
        mobileOpen: false,
        wide: true,
        openModule: activeModule,
        flyout: null,
        navigating: false,
        navFinished: false,

        init() {
            try {
                this.collapsed = localStorage.getItem('ledger.sidebar') === 'collapsed';
            } catch (e) {
                this.collapsed = false;
            }

            this.wide = window.innerWidth >= 1024;

            // The rail is a desktop affordance; below lg the sidebar is a drawer.
            this._onResize = () => {
                this.wide = window.innerWidth >= 1024;
                if (this.wide) this.mobileOpen = false;
                else this.flyout = null;
            };
            window.addEventListener('resize', this._onResize);

            this._onNavigate = () => {
                this.navigating = true;
                this.navFinished = false;
                this.flyout = null;
                this.mobileOpen = false;
                document.documentElement.classList.add('is-module-navigating');
            };

            this._onNavigated = () => {
                this.navigating = false;
                this.navFinished = true;
                document.documentElement.classList.remove('is-module-navigating');

                // Trigger entrance animation on the new module page surface
                const main = document.getElementById('main-content');
                if (main) {
                    main.classList.remove('module-entering');
                    void main.offsetWidth; // Force reflow to replay CSS keyframes
                    main.classList.add('module-entering');
                }

                setTimeout(() => {
                    this.navFinished = false;
                }, 350);
            };

            document.addEventListener('livewire:navigate', this._onNavigate);
            document.addEventListener('livewire:navigated', this._onNavigated);
        },

        destroy() {
            window.removeEventListener('resize', this._onResize);
            document.removeEventListener('livewire:navigate', this._onNavigate);
            document.removeEventListener('livewire:navigated', this._onNavigated);
        },

        /**
         * True only when the 68px icon rail is actually showing: collapsed,
         * on a desktop viewport, and not overridden by the mobile drawer.
         */
        get rail() {
            return this.collapsed && this.wide && !this.mobileOpen;
        },

        toggleCollapse() {
            this.collapsed = !this.collapsed;
            this.flyout = null;
            try {
                localStorage.setItem(
                    'ledger.sidebar',
                    this.collapsed ? 'collapsed' : 'expanded'
                );
            } catch (e) {
                /* storage unavailable — collapse still works for this session */
            }
        },

        /**
         * Accordion: opening a module closes any other. In rail mode the same
         * click opens a floating flyout instead of expanding in place.
         */
        toggleModule(key) {
            if (this.rail) {
                this.flyout = this.flyout === key ? null : key;
                return;
            }
            this.openModule = this.openModule === key ? '' : key;
        },

        isOpen(key) {
            return this.openModule === key;
        },

        closeFlyout() {
            this.flyout = null;
        },
    }));

    /* -------------------------------------------------- topbar realtime UI */
    Alpine.data('topbarRuntime', (depots = [], notifications = [], searchItems = [], defaultIndex = 0) => ({
        depots,
        notifications: notifications.map((notification, index) => ({
            ...notification,
            id: notification.title + '-' + index,
            read: false,
        })),
        searchItems,
        selectedDepotIndex: defaultIndex,
        searchQuery: '',
        searchOpen: false,

        init() {
            try {
                const savedDepotName = localStorage.getItem('microfleet.selectedDepotName');
                if (savedDepotName) {
                    const found = this.depots.findIndex(d => d.name === savedDepotName);
                    if (found !== -1) {
                        this.selectedDepotIndex = found;
                    }
                } else if (this.depots[defaultIndex]) {
                    this.selectedDepotIndex = defaultIndex;
                }

                const readIds = JSON.parse(localStorage.getItem('microfleet.readNotifications') || '[]');
                this.notifications = this.notifications.map((notification) => ({
                    ...notification,
                    read: readIds.includes(notification.id),
                }));
            } catch (e) {
                this.selectedDepotIndex = defaultIndex;
            }
        },

        get selectedDepot() {
            return this.depots[this.selectedDepotIndex] || this.depots[0] || {};
        },

        get unreadCount() {
            return this.notifications.filter((notification) => !notification.read).length;
        },

        get filteredSearch() {
            const query = this.searchQuery.trim().toLowerCase();
            if (!query) return [];

            return this.searchItems
                .filter((item) => `${item.label} ${item.meta}`.toLowerCase().includes(query))
                .slice(0, 6);
        },

        switchDepot(index) {
            if (!this.depots[index]) return;
            this.selectedDepotIndex = index;
            const depot = this.depots[index];

            try {
                localStorage.setItem('microfleet.selectedDepot', String(index));
                localStorage.setItem('microfleet.selectedDepotName', depot.name);
            } catch (e) {
                /* depot still switches for this session */
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch('/depot/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ depot_name: depot.name, depot_id: depot.id }),
            }).then(() => {
                window.location.reload();
            }).catch(() => {
                window.dispatchEvent(
                    new CustomEvent('depot-switched', {
                        detail: this.selectedDepot,
                    })
                );
            });
        },

        clearSearch() {
            this.searchQuery = '';
            this.searchOpen = false;
        },

        markRead(index) {
            if (!this.notifications[index]) return;
            this.notifications[index].read = true;
            this.persistReadNotifications();
        },

        markAllRead() {
            this.notifications = this.notifications.map((notification) => ({
                ...notification,
                read: true,
            }));
            this.persistReadNotifications();
        },

        persistReadNotifications() {
            try {
                localStorage.setItem(
                    'microfleet.readNotifications',
                    JSON.stringify(
                        this.notifications
                            .filter((notification) => notification.read)
                            .map((notification) => notification.id)
                    )
                );
            } catch (e) {
                /* storage unavailable - read state still updates on screen */
            }
        },
    }));

    /* -------------------------------------------------- reusable table search */
    Alpine.data('filterBar', () => ({
        query: '',

        filterRows() {
            const table = this.$root.nextElementSibling?.matches?.('[data-table-root]')
                ? this.$root.nextElementSibling
                : document;
            const rows = Array.from(table.querySelectorAll('tr[data-row]'));
            const query = this.query.trim().toLowerCase();

            rows.forEach((row) => {
                const dataText = Object.values(row.dataset || {}).join(' ');
                const text = `${dataText} ${row.textContent || ''}`.toLowerCase();
                row.hidden = query.length > 0 && !text.includes(query);
            });

            rows
                .filter((row) => !row.hidden)
                .forEach((row, index) => {
                    row.classList.toggle('bg-neutral-50', index % 2 === 1);
                });
        },
    }));

    /* -------------------------------------------------- chart wrapper */
    Alpine.data('ledgerChart', (config = {}) => ({
        chart: null,

        mount() {
            if (!this.$refs.canvas) return;
            this.chart = new Chart(this.$refs.canvas, config);
        },

        destroy() {
            if (this.chart) this.chart.destroy();
            this.chart = null;
        },
    }));

    /* -------------------------------------------------- sortable table */
    Alpine.data('sortableTable', (initialKey = '', initialDir = 'asc') => ({
        sortKey: initialKey,
        sortDir: initialDir,

        sort(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = 'asc';
            }
            this.apply();
        },

        ariaSort(key) {
            if (this.sortKey !== key) return 'none';
            return this.sortDir === 'asc' ? 'ascending' : 'descending';
        },

        apply() {
            const tbody = this.$root.querySelector('tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));
            const key = this.sortKey;
            const dir = this.sortDir === 'asc' ? 1 : -1;

            rows.sort((a, b) => {
                const av = a.dataset[key] ?? '';
                const bv = b.dataset[key] ?? '';
                const an = parseFloat(String(av).replace(/[^0-9.-]/g, ''));
                const bn = parseFloat(String(bv).replace(/[^0-9.-]/g, ''));
                const numeric =
                    !Number.isNaN(an) && !Number.isNaN(bn) && String(av).match(/\d/);

                if (numeric) return (an - bn) * dir;
                return String(av).localeCompare(String(bv)) * dir;
            });

            rows.forEach((r) => tbody.appendChild(r));
            this.restripe(rows);
        },

        // Zebra striping has to be recomputed after a re-order.
        restripe(rows) {
            rows.forEach((r, i) => {
                r.classList.toggle('bg-neutral-50', i % 2 === 1);
            });
        },
    }));

    /* -------------------------------------------------- bulk select */
    Alpine.data('bulkSelect', (ids = []) => ({
        all: ids,
        selected: [],

        get allChecked() {
            return this.all.length > 0 && this.selected.length === this.all.length;
        },

        get someChecked() {
            return this.selected.length > 0 && !this.allChecked;
        },

        toggleAll(event) {
            this.selected = event.target.checked ? [...this.all] : [];
        },

        isSelected(id) {
            return this.selected.includes(id);
        },

        count() {
            return this.selected.length;
        },
    }));

    /* -------------------------------------------------- collection sheet */
    Alpine.data('collectionSheet', (groups = []) => ({
        groups: groups.map((g) => ({
            ...g,
            rows: g.rows.map((r) => ({ ...r, collected: Number(r.collected) })),
        })),

        groupDue(g) {
            return g.rows.reduce((s, r) => s + Number(r.due || 0), 0);
        },

        groupCollected(g) {
            return g.rows.reduce((s, r) => s + Number(r.collected || 0), 0);
        },

        grandDue() {
            return this.groups.reduce((s, g) => s + this.groupDue(g), 0);
        },

        grandCollected() {
            return this.groups.reduce((s, g) => s + this.groupCollected(g), 0);
        },

        paidCount() {
            return this.groups.reduce(
                (s, g) => s + g.rows.filter((r) => Number(r.collected) > 0).length,
                0
            );
        },

        totalCount() {
            return this.groups.reduce((s, g) => s + g.rows.length, 0);
        },

        rowStatus(r) {
            const c = Number(r.collected || 0);
            const d = Number(r.due || 0);
            if (c <= 0) return 'Pending';
            return c >= d ? 'Paid' : 'Partial';
        },

        markFullyPaid(r) {
            r.collected = Number(r.due);
        },

        peso,
    }));

    /* -------------------------------------------------- record payment modal */
    Alpine.data('paymentForm', (outstanding = 0, suggested = 0) => ({
        outstanding: Number(outstanding),
        amount: Number(suggested),
        method: 'Cash',

        get remaining() {
            const r = this.outstanding - Number(this.amount || 0);
            return r > 0 ? r : 0;
        },

        get overpaid() {
            return Number(this.amount || 0) > this.outstanding;
        },

        peso,
    }));

    /* -------------------------------------------------- teller denominations */
    Alpine.data('denominationGrid', (rows = [], expected = 0) => ({
        rows: rows.map((r) => ({ ...r, count: Number(r.count) })),
        expected: Number(expected),

        lineTotal(r) {
            return Number(r.value) * Number(r.count || 0);
        },

        total() {
            return this.rows.reduce((s, r) => s + this.lineTotal(r), 0);
        },

        variance() {
            return this.total() - this.expected;
        },

        peso,
    }));

    /* -------------------------------------------------- permission matrix */
    Alpine.data('permissionMatrix', (grid = {}) => ({
        grid,

        toggleRow(rowKey, on) {
            Object.keys(this.grid[rowKey] || {}).forEach((role) => {
                Object.keys(this.grid[rowKey][role]).forEach((perm) => {
                    this.grid[rowKey][role][perm] = on;
                });
            });
        },

        toggleColumn(role, on) {
            Object.keys(this.grid).forEach((rowKey) => {
                if (!this.grid[rowKey][role]) return;
                Object.keys(this.grid[rowKey][role]).forEach((perm) => {
                    this.grid[rowKey][role][perm] = on;
                });
            });
        },

        rowAllOn(rowKey) {
            const row = this.grid[rowKey] || {};
            return Object.values(row).every((r) => Object.values(r).every(Boolean));
        },

        columnAllOn(role) {
            return Object.values(this.grid).every((row) =>
                row[role] ? Object.values(row[role]).every(Boolean) : true
            );
        },
    }));

    /* -------------------------------------------------- multi-step wizard */
    Alpine.data('wizard', (steps = 1, start = 1) => ({
        step: start,
        total: steps,

        next() {
            if (this.step < this.total) this.step++;
        },

        back() {
            if (this.step > 1) this.step--;
        },

        goTo(n) {
            if (n >= 1 && n <= this.total) this.step = n;
        },

        state(n) {
            if (n < this.step) return 'complete';
            if (n === this.step) return 'current';
            return 'upcoming';
        },
    }));
});

/* -------------------------------------------------- Module click ripple animation */
document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-module-btn], [data-module-link]');
    if (!target) return;

    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 1.5;
    const ripple = document.createElement('span');
    ripple.className = 'module-ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${event.clientY - rect.top - size / 2}px`;

    target.appendChild(ripple);
    setTimeout(() => {
        ripple.remove();
    }, 500);
});

/* Initial module entrance trigger on first page load */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const main = document.getElementById('main-content');
        if (main && !main.classList.contains('module-entering')) {
            main.classList.add('module-entering');
        }
    });
} else {
    const main = document.getElementById('main-content');
    if (main && !main.classList.contains('module-entering')) {
        main.classList.add('module-entering');
    }
}

