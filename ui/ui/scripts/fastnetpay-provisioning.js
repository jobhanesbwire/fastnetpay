(function () {
    var root = document.querySelector('.fnp-provision-page[data-base-url]');
    if (!root) {
        return;
    }

    var form = document.getElementById('fnpProvisionForm');
    var baseUrl = root.getAttribute('data-base-url') || '';
    var routerId = root.getAttribute('data-router-id') || '0';
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.fnp-provision-step-button'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.fnp-provision-step-panel'));
    var currentIndex = 0;
    var latestScript = '';

    function route(name) {
        return baseUrl + name + '/' + routerId;
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function setBusy(button, busy, label) {
        if (!button) {
            return;
        }
        if (busy) {
            button.setAttribute('data-original-text', button.innerHTML);
            button.disabled = true;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + (label || 'Working');
        } else {
            button.disabled = false;
            if (button.getAttribute('data-original-text')) {
                button.innerHTML = button.getAttribute('data-original-text');
            }
        }
    }

    function activate(target) {
        buttons.forEach(function (button, index) {
            var active = button.getAttribute('data-target') === target;
            button.classList.toggle('active', active);
            if (active) {
                currentIndex = index;
            }
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('active', panel.id === target);
        });
    }

    function request(url) {
        return fetch(url, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.json().then(function (json) {
                if (!response.ok || json.ok === false) {
                    throw new Error(json.message || 'Request failed');
                }
                return json;
            });
        });
    }

    function renderDetect(data) {
        var target = document.getElementById('fnpProvisionDetectResult');
        var interfaceTarget = document.getElementById('fnpProvisionInterfaces');
        var html = '<div class="fnp-provision-detect-card is-success">';
        html += '<strong><i class="fa fa-check-circle"></i> Connected successfully</strong>';
        html += '<span>RouterOS ' + escapeHtml(data.version || 'unknown') + ' · ' + escapeHtml(data.board_name || 'board unknown') + ' · ' + escapeHtml(data.identity || 'unnamed') + '</span>';
        if (data.api_user) {
            html += '<small>FASTNETPAY API user: ' + escapeHtml(data.api_user.username || 'fastnet-api-usr') + ' · ' + escapeHtml(data.api_user.status || 'checked') + ' · ' + escapeHtml(data.api_user.message || '') + '</small>';
        }
        if (data.existing) {
            html += '<small>Existing: ' + data.existing.hotspots + ' hotspot, ' + data.existing.pppoe_servers + ' PPPoE, ' + data.existing.dhcp_servers + ' DHCP, ' + data.existing.firewall_rules + ' firewall rules.</small>';
        }
        if (data.vpn) {
            html += '<small>VPN: ' + escapeHtml(data.vpn.message || '') + ' Recommended mode: ' + escapeHtml((data.vpn.recommended_mode || 'local').toUpperCase()) + '.</small>';
            if (data.vpn.wireguard_supported === false) {
                var checkedWireguard = form.querySelector('input[name="connection_mode"][value="wireguard"]:checked');
                var sstp = form.querySelector('input[name="connection_mode"][value="sstp"]');
                if (checkedWireguard && sstp) {
                    sstp.checked = true;
                }
            }
        }
        if (data.warnings && data.warnings.length) {
            html += '<ul>' + data.warnings.map(function (warning) {
                return '<li>' + escapeHtml(warning) + '</li>';
            }).join('') + '</ul>';
        }
        html += '</div>';
        target.innerHTML = html;

        if (interfaceTarget && data.interfaces) {
            populateInterfaceOptions(data.interfaces);
            interfaceTarget.innerHTML = data.interfaces.map(function (item) {
                return '<button type="button" class="fnp-interface-chip" data-interface="' + escapeHtml(item.name) + '">' +
                    '<i class="fa fa-ethernet"></i> ' + escapeHtml(item.name) +
                    '<small>' + escapeHtml(item.type || '') + (item.running === 'true' ? ' · running' : '') + '</small>' +
                    '</button>';
            }).join('');
        }
    }

    function populateInterfaceOptions(interfaces) {
        var list = document.getElementById('fnpProvisionInterfaceOptions');
        if (!list) {
            return;
        }
        var seen = {};
        ['ether1', 'ether2', 'ether3', 'ether4', 'fastnetpay-bridge'].concat((interfaces || []).map(function (item) {
            return item.name || '';
        })).forEach(function (name) {
            if (!name || seen[name]) {
                return;
            }
            seen[name] = true;
            var option = document.createElement('option');
            option.value = name;
            list.appendChild(option);
        });
    }

    function renderWarnings(warnings) {
        var target = document.getElementById('fnpProvisionWarnings');
        if (!target) {
            return;
        }
        if (!warnings || !warnings.length) {
            target.innerHTML = '<div class="fnp-provision-alert is-ready"><i class="fa fa-check-circle"></i> No blocking warnings. Review the script before applying.</div>';
            return;
        }
        target.innerHTML = '<div class="fnp-provision-alert is-warning"><i class="fa fa-exclamation-triangle"></i><div><strong>Review before applying:</strong><ul>' +
            warnings.map(function (warning) {
                return '<li>' + escapeHtml(warning) + '</li>';
            }).join('') +
            '</ul></div></div>';
    }

    function renderRun(data) {
        var target = document.getElementById('fnpProvisionApplyResult');
        if (!target) {
            return;
        }
        var html = '<div class="fnp-provision-run-card ' + (data.ok ? 'is-success' : 'is-error') + '">';
        html += '<h4>' + (data.ok ? 'Provisioning completed' : 'Provisioning stopped') + '</h4>';
        html += '<p>Run #' + escapeHtml(data.run_id || '') + (data.backup_file ? ' · Backup: ' + escapeHtml(data.backup_file) : '') + '</p>';
        html += '<div class="fnp-provision-run-steps">';
        (data.steps || []).forEach(function (step) {
            html += '<div><span class="fnp-provision-status is-' + escapeHtml(step.status) + '">' + escapeHtml(step.status) + '</span><strong>' + escapeHtml(step.name) + '</strong><small>' + escapeHtml(step.message) + '</small></div>';
        });
        html += '</div></div>';
        target.innerHTML = html;
        if (data.final_result) {
            renderFinal(data.final_result);
        }
    }

    function renderPortalRefresh(data) {
        var target = document.getElementById('fnpProvisionPortalRefreshResult');
        if (!target) {
            return;
        }
        target.innerHTML = '<div class="fnp-provision-run-card ' + (data.ok ? 'is-success' : 'is-error') + '">' +
            '<h4>' + (data.ok ? 'Captive portal files refreshed' : 'Portal refresh failed') + '</h4>' +
            '<p>' + escapeHtml(data.message || '') + '</p>' +
            '<small>Router #' + escapeHtml(data.router_id || '') + ' · Connection: ' + escapeHtml(data.connection_status || '') + '</small>' +
            '</div>';
    }

    function renderFinal(result) {
        var target = document.getElementById('fnpProvisionFinalResult');
        if (!target) {
            return;
        }
        if (!result) {
            target.innerHTML = '<div class="fnp-provision-alert is-warning"><i class="fa fa-info-circle"></i> No live final result is available yet.</div>';
            return;
        }
        var status = result.status || 'warning';
        var html = '<div class="fnp-final-summary is-' + escapeHtml(status) + '">';
        html += '<div><strong>' + escapeHtml(result.message || 'Live checks completed.') + '</strong>';
        html += '<small>Checked ' + escapeHtml(result.checked_at || '') + ' · Bridge ' + escapeHtml(result.bridge || 'none') + ' · Hotspot ' + escapeHtml(result.hotspot_interface || '') + '</small></div>';
        html += '<span class="fnp-provision-status is-' + escapeHtml(status) + '">' + escapeHtml(status) + '</span>';
        html += '</div>';
        html += '<div class="fnp-final-grid">';
        (result.items || []).forEach(function (item) {
            html += '<div class="fnp-final-item is-' + escapeHtml(item.status || 'warning') + '">';
            html += '<span class="fnp-provision-status is-' + escapeHtml(item.status || 'warning') + '">' + escapeHtml(item.status || '') + '</span>';
            html += '<strong>' + escapeHtml(item.label || '') + '</strong>';
            html += '<em>' + escapeHtml(item.value || '') + '</em>';
            html += '<small>' + escapeHtml(item.message || '') + '</small>';
            html += '</div>';
        });
        html += '</div>';
        if (result.portal_url) {
            html += '<div class="fnp-provision-callout">Portal API: <code>' + escapeHtml(result.portal_url) + '</code></div>';
        }
        target.innerHTML = html;
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            activate(button.getAttribute('data-target'));
        });
    });

    var prev = document.getElementById('fnpProvisionPrev');
    var next = document.getElementById('fnpProvisionNext');
    if (prev) {
        prev.addEventListener('click', function () {
            var index = Math.max(0, currentIndex - 1);
            activate(buttons[index].getAttribute('data-target'));
        });
    }
    if (next) {
        next.addEventListener('click', function () {
            var index = Math.min(buttons.length - 1, currentIndex + 1);
            activate(buttons[index].getAttribute('data-target'));
        });
    }

    var detect = document.getElementById('fnpProvisionDetect');
    if (detect) {
        detect.addEventListener('click', function () {
            var target = document.getElementById('fnpProvisionDetectResult');
            setBusy(detect, true, 'Testing');
            request(route('routers/provision-detect')).then(function (data) {
                renderDetect(data);
            }).catch(function (error) {
                target.innerHTML = '<div class="fnp-provision-detect-card is-error"><strong><i class="fa fa-times-circle"></i> Connection failed</strong><span>' + escapeHtml(error.message) + '</span></div>';
            }).finally(function () {
                setBusy(detect, false);
            });
        });
    }

    var preview = document.getElementById('fnpProvisionPreview');
    if (preview) {
        preview.addEventListener('click', function () {
            setBusy(preview, true, 'Generating');
            request(route('routers/provision-preview')).then(function (data) {
                latestScript = data.script || '';
                document.getElementById('fnpProvisionScript').textContent = latestScript || 'No commands generated.';
                renderWarnings(data.warnings || []);
            }).catch(function (error) {
                renderWarnings([error.message]);
            }).finally(function () {
                setBusy(preview, false);
            });
        });
    }

    var copy = document.getElementById('fnpProvisionCopy');
    if (copy) {
        copy.addEventListener('click', function () {
            var script = latestScript || document.getElementById('fnpProvisionScript').textContent || '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(script);
            } else {
                var temp = document.createElement('textarea');
                temp.value = script;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
            }
            copy.innerHTML = '<i class="fa fa-check"></i> Copied';
            setTimeout(function () {
                copy.innerHTML = '<i class="fa fa-copy"></i> Copy Commands';
            }, 1400);
        });
    }

    var download = document.getElementById('fnpProvisionDownload');
    if (download) {
        download.addEventListener('click', function () {
            var script = latestScript || document.getElementById('fnpProvisionScript').textContent || '';
            var blob = new Blob([script], { type: 'text/plain;charset=utf-8' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'fastnetpay-router-provisioning.rsc';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
        });
    }

    var run = document.getElementById('fnpProvisionRun');
    if (run) {
        run.addEventListener('click', function () {
            if (!confirm('Apply provisioning to this MikroTik router now? Confirm you have reviewed the preview and have alternative router access.')) {
                return;
            }
            setBusy(run, true, 'Applying');
            request(route('routers/provision-run')).then(function (data) {
                renderRun(data);
                activate('step-final');
            }).catch(function (error) {
                renderRun({ ok: false, run_id: '', steps: [{ name: 'Provisioning', status: 'failed', message: error.message }] });
            }).finally(function () {
                setBusy(run, false);
            });
        });
    }

    var refreshPortal = document.getElementById('fnpProvisionRefreshPortal');
    if (refreshPortal) {
        refreshPortal.addEventListener('click', function () {
            setBusy(refreshPortal, true, 'Refreshing');
            request(route('routers/provision-refresh-portal')).then(function (data) {
                renderPortalRefresh(data);
            }).catch(function (error) {
                renderPortalRefresh({ ok: false, message: error.message, router_id: routerId, connection_status: 'failed' });
            }).finally(function () {
                setBusy(refreshPortal, false);
            });
        });
    }

    var finalTest = document.getElementById('fnpProvisionFinalTest');
    if (finalTest) {
        finalTest.addEventListener('click', function () {
            setBusy(finalTest, true, 'Testing');
            request(route('routers/provision-final-test')).then(function (data) {
                renderFinal(data.final_result);
            }).catch(function (error) {
                renderFinal({
                    status: 'failed',
                    message: error.message,
                    checked_at: '',
                    items: []
                });
            }).finally(function () {
                setBusy(finalTest, false);
            });
        });
    }

    document.addEventListener('click', function (event) {
        var chip = event.target.closest('.fnp-interface-chip');
        if (!chip) {
            return;
        }
        var name = chip.getAttribute('data-interface');
        var focused = document.activeElement && document.activeElement.matches && document.activeElement.matches('input[name$="_interface"]') ? document.activeElement : null;
        var empty = focused || Array.prototype.slice.call(form.querySelectorAll('input[name$="_interface"]')).filter(function (input) {
            return input.value.trim() === '';
        })[0];
        if (empty) {
            empty.value = name;
        }
    });
})();
