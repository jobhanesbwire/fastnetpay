(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn);
    }

    function text(value, fallback) {
        if (value === null || value === undefined || value === '') {
            return fallback || '';
        }
        return String(value);
    }

    function escapeHtml(value) {
        return text(value).replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }

    function endpoint(page, action) {
        return page.baseUrl + 'plugin/' + action + (page.routerId ? '/' + page.routerId : '');
    }

    function fetchJson(url, options) {
        return fetch(url, options || {}).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok && !data.message) {
                    data.message = 'Request failed with HTTP ' + response.status;
                }
                return data;
            });
        });
    }

    function showError(page, message) {
        var target = page.el.querySelector('[data-monitor-error]');
        if (!target) return;
        if (!message) {
            target.style.display = 'none';
            target.textContent = '';
            return;
        }
        target.style.display = 'block';
        target.textContent = message;
    }

    function setStat(page, key, value, small) {
        var items = page.el.querySelectorAll('[data-stat="' + key + '"]');
        for (var i = 0; i < items.length; i++) {
            items[i].textContent = text(value, '0');
        }
        if (small) {
            var smallItems = page.el.querySelectorAll('[data-stat-small="' + key + '"]');
            for (var j = 0; j < smallItems.length; j++) {
                smallItems[j].textContent = text(small, '');
            }
        }
    }

    function statusBadge(status) {
        var label = status === 'online' ? 'Online' : (status === 'disabled' ? 'Disabled' : 'Offline');
        return '<span class="fnp-status-badge is-' + escapeHtml(status) + '"><i></i>' + label + '</span>';
    }

    function profileBadge(value) {
        if (!value) return '<span class="text-muted">-</span>';
        return '<span class="fnp-soft-badge">' + escapeHtml(value) + '</span>';
    }

    function initChart(page) {
        var canvas = page.el.querySelector('[data-traffic-chart]');
        if (!canvas || !window.Chart) return;
        page.chartData = {
            labels: [],
            tx: [],
            rx: []
        };
        page.chart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: page.chartData.labels,
                datasets: [
                    {
                        label: 'TX',
                        data: page.chartData.tx,
                        borderColor: '#41a146',
                        backgroundColor: 'rgba(65, 161, 70, 0.12)',
                        pointRadius: 0,
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'RX',
                        data: page.chartData.rx,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.10)',
                        pointRadius: 0,
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });
    }

    function updateTraffic(page) {
        var select = page.el.querySelector('[data-interface-select]');
        if (!select || !select.value || !page.chart) return;
        fetchJson(endpoint(page, 'mikrotik_monitor_traffic_update') + '&interface=' + encodeURIComponent(select.value))
            .then(function (data) {
                if (!data || data.error || !data.rows) return;
                var label = data.labels && data.labels[0] ? data.labels[0] : new Date().toLocaleTimeString();
                var tx = parseInt(data.rows.tx && data.rows.tx[0] ? data.rows.tx[0] : 0, 10);
                var rx = parseInt(data.rows.rx && data.rows.rx[0] ? data.rows.rx[0] : 0, 10);
                page.chartData.labels.push(label);
                page.chartData.tx.push(tx);
                page.chartData.rx.push(rx);
                while (page.chartData.labels.length > 16) {
                    page.chartData.labels.shift();
                    page.chartData.tx.shift();
                    page.chartData.rx.shift();
                }
                page.chart.update();
                var txEl = page.el.querySelector('[data-traffic-tx]');
                var rxEl = page.el.querySelector('[data-traffic-rx]');
                if (txEl) txEl.textContent = formatBytes(tx);
                if (rxEl) rxEl.textContent = formatBytes(rx);
            });
    }

    function formatBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes <= 0) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return (bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1) + ' ' + units[index];
    }

    function populateInterfaces(page, interfaces) {
        var select = page.el.querySelector('[data-interface-select]');
        var list = page.el.querySelector('[data-interface-list]');
        if (select) {
            select.innerHTML = '';
            interfaces.forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.name;
                option.textContent = item.name;
                select.appendChild(option);
            });
        }
        if (list) {
            if (!interfaces.length) {
                list.innerHTML = '<div class="fnp-empty-row">No interface data available.</div>';
            } else {
                list.innerHTML = interfaces.map(function (item) {
                    return '<div class="fnp-interface-row">' +
                        '<span><i class="' + (item.running ? 'is-online' : 'is-offline') + '"></i>' + escapeHtml(item.name) + '</span>' +
                        '<b>' + escapeHtml(item.total) + '</b>' +
                    '</div>';
                }).join('');
            }
        }
    }

    function loadDashboard(page) {
        fetchJson(endpoint(page, 'mikrotik_monitor_snapshot')).then(function (data) {
            showError(page, data.ok ? '' : data.error);
            setStat(page, 'router_count', text(data.router_count, 0) + ' total routers');
            setStat(page, 'online_router_count', text(data.online_router_count, 0));
            setStat(page, 'local_hotspot_clients', text(data.local_hotspot_clients, 0) + ' local clients');
            setStat(page, 'local_pppoe_clients', text(data.local_pppoe_clients, 0) + ' local clients');
            setStat(page, 'active_hotspot', data.active_hotspot);
            setStat(page, 'active_pppoe', data.active_pppoe);
            setStat(page, 'total_traffic', data.total_traffic);
            setStat(page, 'hotspot_servers', data.hotspot_servers);
            setStat(page, 'total_hotspot', text(data.total_hotspot, 0) + ' hotspot users');
            setStat(page, 'cpu_load', data.resource && data.resource.cpu_load ? data.resource.cpu_load + '%' : '0%');
            setStat(page, 'uptime', data.resource && data.resource.uptime ? data.resource.uptime : 'uptime unavailable');
            setStat(page, 'version', data.resource && data.resource.version ? data.resource.version : 'N/A');
            setStat(page, 'free_memory', data.resource && data.resource.free_memory ? data.resource.free_memory : 'N/A');
            populateInterfaces(page, data.interfaces || []);
            if (!page.chart) initChart(page);
            updateTraffic(page);
        });
    }

    function uniqueProfiles(rows) {
        var seen = {};
        rows.forEach(function (row) {
            if (row.profile) seen[row.profile] = true;
        });
        return Object.keys(seen).sort();
    }

    function syncProfileFilter(page, rows) {
        var select = page.el.querySelector('[data-profile-filter]');
        if (!select) return;
        var current = select.value || 'all';
        select.innerHTML = '<option value="all">All profiles</option>' + uniqueProfiles(rows).map(function (profile) {
            return '<option value="' + escapeHtml(profile) + '">' + escapeHtml(profile) + '</option>';
        }).join('');
        select.value = current;
        if (!select.value) select.value = 'all';
    }

    function filterRows(page) {
        var search = text(page.el.querySelector('[data-monitor-search]') && page.el.querySelector('[data-monitor-search]').value).toLowerCase();
        var status = text(page.el.querySelector('[data-status-filter]') && page.el.querySelector('[data-status-filter]').value, 'all');
        var profile = text(page.el.querySelector('[data-profile-filter]') && page.el.querySelector('[data-profile-filter]').value, 'all');
        return page.rows.filter(function (row) {
            var haystack = Object.keys(row).map(function (key) { return text(row[key]); }).join(' ').toLowerCase();
            if (search && haystack.indexOf(search) === -1) return false;
            if (status !== 'all' && row.status !== status) return false;
            if (profile !== 'all' && row.profile !== profile) return false;
            return true;
        });
    }

    function renderRows(page) {
        var body = page.el.querySelector('[data-monitor-rows]');
        if (!body) return;
        var rows = filterRows(page);
        if (!rows.length) {
            var colspan = page.mode === 'hotspot' ? 12 : 11;
            body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-muted fnp-empty-row">No matching sessions found.</td></tr>';
            return;
        }
        body.innerHTML = rows.map(function (row, index) {
            row.index = index + 1;
            if (page.mode === 'hotspot') {
                return '<tr>' +
                    '<td>' + row.index + '</td>' +
                    '<td><b>' + escapeHtml(row.username) + '</b><span class="fnp-table-subtext">' + escapeHtml(row.comment) + '</span></td>' +
                    '<td>' + escapeHtml(row.address || '-') + '</td>' +
                    '<td class="fnp-mono">' + escapeHtml(row.mac || '-') + '</td>' +
                    '<td>' + escapeHtml(row.uptime || '-') + '</td>' +
                    '<td>' + escapeHtml(row.rx || '0 B') + '</td>' +
                    '<td>' + escapeHtml(row.tx || '0 B') + '</td>' +
                    '<td>' + escapeHtml(row.total || '0 B') + '</td>' +
                    '<td>' + profileBadge(row.profile) + '</td>' +
                    '<td>' + escapeHtml(row.server || '-') + '</td>' +
                    '<td>' + statusBadge(row.status) + '</td>' +
                    '<td class="fnp-table-actions">' + actionButtons(page, row) + '</td>' +
                '</tr>';
            }
            return '<tr>' +
                '<td><b>' + escapeHtml(row.username) + '</b><span class="fnp-table-subtext">' + escapeHtml(row.comment) + '</span></td>' +
                '<td>' + escapeHtml(row.address || '-') + '</td>' +
                '<td>' + escapeHtml(row.uptime || '-') + '</td>' +
                '<td>' + escapeHtml(row.service || 'pppoe') + '</td>' +
                '<td>' + profileBadge(row.profile) + '</td>' +
                '<td class="fnp-mono">' + escapeHtml(row.caller_id || '-') + '</td>' +
                '<td>' + escapeHtml(row.rx || '0 B') + '</td>' +
                '<td>' + escapeHtml(row.tx || '0 B') + '</td>' +
                '<td>' + escapeHtml(row.total || '0 B') + '</td>' +
                '<td>' + statusBadge(row.status) + '</td>' +
                '<td class="fnp-table-actions">' + actionButtons(page, row) + '</td>' +
            '</tr>';
        }).join('');
    }

    function actionButtons(page, row) {
        var disconnectDisabled = row.status !== 'online' ? ' disabled' : '';
        return '<button type="button" class="btn btn-info btn-xs" data-session-detail="' + escapeHtml(row.username) + '" title="Details"><i class="fa fa-eye"></i></button> ' +
            '<button type="button" class="btn btn-danger btn-xs"' + disconnectDisabled + ' data-session-disconnect="' + escapeHtml(row.username) + '" title="Disconnect"><i class="fa fa-chain-broken"></i></button>';
    }

    function updateTableStats(page, data) {
        var stats = data.stats || {};
        setStat(page, 'online', stats.online || 0);
        setStat(page, 'offline', stats.offline || 0);
        setStat(page, 'total', stats.total || 0);
        setStat(page, 'disabled', stats.disabled || 0);
        setStat(page, 'servers', stats.servers || 0);
        if (page.mode === 'hotspot') {
            var total = (data.rows || []).reduce(function (sum, row) {
                return sum + (Number(row.rx_raw) || 0) + (Number(row.tx_raw) || 0);
            }, 0);
            setStat(page, 'total_traffic', formatBytes(total));
        }
    }

    function loadTable(page) {
        var action = page.mode === 'hotspot' ? 'mikrotik_monitor_hotspot_data' : 'mikrotik_monitor_pppoe_data';
        fetchJson(endpoint(page, action)).then(function (data) {
            page.rows = data.rows || [];
            showError(page, data.ok ? '' : data.error);
            syncProfileFilter(page, page.rows);
            updateTableStats(page, data);
            renderRows(page);
        });
    }

    function openDetails(page, username) {
        var row = page.rows.filter(function (item) { return item.username === username; })[0];
        if (!row) return;
        var modal = document.getElementById('fnpSessionModal');
        var body = modal ? modal.querySelector('[data-session-details]') : null;
        if (!modal || !body) return;
        body.innerHTML = '<div class="fnp-session-grid">' + Object.keys(row).map(function (key) {
            return '<div><span>' + escapeHtml(key.replace(/_/g, ' ')) + '</span><strong>' + escapeHtml(row[key] || '-') + '</strong></div>';
        }).join('') + '</div>';
        if (window.jQuery) window.jQuery(modal).modal('show');
    }

    function disconnect(page, username) {
        var proceed = window.Swal ?
            window.Swal.fire({ icon: 'warning', title: 'Disconnect session?', text: username, showCancelButton: true, confirmButtonText: 'Disconnect' }) :
            Promise.resolve({ isConfirmed: window.confirm('Disconnect ' + username + '?') });
        proceed.then(function (result) {
            if (!result.isConfirmed) return;
            var body = new URLSearchParams();
            body.append('csrf_token', page.csrf);
            body.append('router', page.routerId);
            body.append('username', username);
            body.append('type', page.mode);
            fetchJson(page.baseUrl + 'plugin/mikrotik_monitor_disconnect', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function (data) {
                if (window.Swal) {
                    window.Swal.fire({ icon: data.ok ? 'success' : 'error', title: data.message || (data.ok ? 'Disconnected' : 'Failed') });
                }
                loadTable(page);
            });
        });
    }

    function exportCsv(page) {
        var rows = filterRows(page);
        if (!rows.length) return;
        var columns = Object.keys(rows[0]).filter(function (key) { return key !== 'index'; });
        var csv = [columns.join(',')].concat(rows.map(function (row) {
            return columns.map(function (key) {
                return '"' + text(row[key]).replace(/"/g, '""') + '"';
            }).join(',');
        })).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'fastnetpay-' + page.mode + '-monitor.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function bindTable(page) {
        page.el.addEventListener('input', function (event) {
            if (event.target.matches('[data-monitor-search], [data-status-filter], [data-profile-filter]')) renderRows(page);
        });
        page.el.addEventListener('click', function (event) {
            var detail = event.target.closest('[data-session-detail]');
            var disconnectButton = event.target.closest('[data-session-disconnect]');
            var exportButton = event.target.closest('[data-export-csv]');
            var refreshButton = event.target.closest('[data-monitor-refresh]');
            var sortHeader = event.target.closest('[data-sort]');
            if (detail) openDetails(page, detail.getAttribute('data-session-detail'));
            if (disconnectButton && !disconnectButton.disabled) disconnect(page, disconnectButton.getAttribute('data-session-disconnect'));
            if (exportButton) exportCsv(page);
            if (refreshButton) page.mode === 'dashboard' ? loadDashboard(page) : loadTable(page);
            if (sortHeader) sortRows(page, sortHeader.getAttribute('data-sort'));
        });
    }

    function sortRows(page, key) {
        page.sortDir = page.sortKey === key && page.sortDir === 'asc' ? 'desc' : 'asc';
        page.sortKey = key;
        page.rows.sort(function (a, b) {
            var av = text(a[key]).toLowerCase();
            var bv = text(b[key]).toLowerCase();
            if (av === bv) return 0;
            return (av > bv ? 1 : -1) * (page.sortDir === 'asc' ? 1 : -1);
        });
        renderRows(page);
    }

    function startTimers(page) {
        var refresh = function () {
            var checkbox = page.el.querySelector('[data-auto-refresh]');
            if (checkbox && !checkbox.checked) return;
            page.mode === 'dashboard' ? loadDashboard(page) : loadTable(page);
        };
        page.refreshTimer = setInterval(refresh, 15000);
        if (page.el.querySelector('[data-traffic-chart]')) {
            initChart(page);
            page.trafficTimer = setInterval(function () { updateTraffic(page); }, 2500);
        }
    }

    ready(function () {
        var pages = document.querySelectorAll('.fnp-monitor-page');
        for (var i = 0; i < pages.length; i++) {
            var page = {
                el: pages[i],
                mode: pages[i].getAttribute('data-monitor-mode'),
                routerId: pages[i].getAttribute('data-router-id'),
                baseUrl: pages[i].getAttribute('data-base-url'),
                csrf: pages[i].getAttribute('data-csrf'),
                rows: [],
                sortKey: '',
                sortDir: 'asc'
            };
            bindTable(page);
            if (page.mode === 'dashboard') {
                loadDashboard(page);
            } else {
                loadDashboard(page);
                loadTable(page);
            }
            startTimers(page);
        }
    });
}());
