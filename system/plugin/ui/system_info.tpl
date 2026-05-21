{include file="sections/header.tpl"}

<style>
    .status-card {
        border-radius: 15px;
        border: none;
        transition: transform 0.3s;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .status-card:hover {
        transform: translateY(-5px);
    }

    .progress-circle {
        width: 100px;
        height: 100px;
        position: relative;
    }

    .progress-circle svg {
        width: 100%;
        height: 100%;
    }

    .progress-circle circle {
        fill: none;
        stroke-width: 8;
        stroke-linecap: round;
    }

    .progress-circle .bg {
        stroke: #eee;
    }

    .progress-circle .progress {
        stroke: #4CAF50;
        transition: stroke-dashoffset 0.5s ease-out;
    }

    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.2rem;
        font-weight: bold;
    }

    .system-info .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #007bff;
    }

    .system-info .label {
        font-weight: 600;
        color: #6c757d;
    }

    .system-info .value {
        float: right;
        color: #495057;
    }

    .service-status-item {
        display: flex;
        align-items: center;
        padding: 15px;
        margin: 10px 0;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .service-icon {
        font-size: 1.5rem;
        margin-right: 15px;
    }

    .bg-gradient-success {
        background: linear-gradient(45deg, #28a745, #20c997);
    }

    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #ff6b6b);
    }

    .bg-gradient-info {
        background: linear-gradient(45deg, #17a2b8, #2d9cca);
    }

    .dark-mode {
        --primary-color: #6c8dfa;
        --secondary-color: #a0aec0;
        --success-color: #48bb78;
        --danger-color: #f56565;
        --light-color: #2d3748;
        --dark-color: #e2e8f0;
        --bg-color: #1a202c;
        --text-color: #e2e8f0;
        --card-bg: #2d3748;
        --border-color: rgba(255, 255, 255, 0.1);
    }

    .dark-mode body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .dark-mode .status-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }

    .dark-mode .system-info .info-item {
        background: rgba(45, 55, 72, 0.5);
        border-left: 4px solid var(--primary-color);
    }

    .dark-mode .service-status-item {
        background: rgba(45, 55, 72, 0.7);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .dark-mode .progress-circle .bg {
        stroke: rgba(255, 255, 255, 0.1);
    }

    .dark-mode .progress-bar {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .dark-mode .bg-gradient-success {
        background: linear-gradient(45deg, #2f855a, #48bb78);
    }

    .dark-mode .bg-gradient-danger {
        background: linear-gradient(45deg, #c53030, #f56565);
    }

    .dark-mode .bg-gradient-info {
        background: linear-gradient(45deg, #2b6cb0, #4299e1);
    }

    .dark-mode .card-header {
        background-color: rgba(0, 0, 0, 0.3) !important;
        border-bottom: 1px solid var(--border-color);
    }

    .dark-mode .alert-success {
        background-color: #2f855a;
        border-color: #276749;
        color: #ffffff;
    }

    .dark-mode .alert-danger {
        background-color: #c53030;
        border-color: #9b2c2c;
        color: #ffffff;
    }

    .dark-mode .btn-gradient-info {
        background: linear-gradient(45deg, #2b6cb0, #4299e1);
        border-color: #2b6cb0;
    }

    .dark-mode .text-muted {
        color: #a0aec0 !important;
    }

    .dark-mode .service-status-item.active {
        border-left: 3px solid var(--success-color);
    }

    .dark-mode .service-status-item.inactive {
        border-left: 3px solid var(--danger-color);
    }

    .dark-mode .label {
        color: var(--secondary-color);
    }

    .dark-mode .value {
        color: var(--dark-color);
    }

    .btn-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #ff3b3b);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffd700);
        border: none;
        color: #212529;
        transition: all 0.3s ease;
    }

    .btn-gradient-danger:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }

    .btn-gradient-warning:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
    }

    .control-panel .btn {
        margin: 8px;
        min-width: 180px;
        position: relative;
        overflow: hidden;
    }

    .control-panel .btn i {
        margin-right: 8px;
    }

    .service-controls {
        border-top: 2px solid rgba(0, 0, 0, 0.1);
        padding-top: 20px;
        margin-top: 20px;
    }

    .service-btn {
        margin: 5px;
        transition: all 0.3s ease;
    }

    .service-btn:hover {
        transform: translateY(-2px);
    }

    @keyframes pulse-danger {
        0% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    .btn-shutdown {
        animation: pulse-danger 2s infinite;
    }

    .dark-mode .control-panel .btn {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .dark-mode .service-controls {
        border-color: rgba(255, 255, 255, 0.1);
    }
</style>
{if isset($message)}
<div class="alert alert-{if $notify_t == 's'}success{else}danger{/if} alert-dismissible fade show" role="alert">
    <div>{$message}</div>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
{/if}

<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-server"></i> Server Status Dashboard</h2>

    <!-- System Information Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card status-card shadow-lg">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-microchip text-primary"></i> CPU Usage</h5>
                    <div class="d-flex align-items-center">
                        <div class="progress-circle" data-percentage="{$cpu_info.usage_percentage}">
                            <svg viewBox="0 0 100 100">
                                <circle class="bg" cx="50" cy="50" r="45"></circle>
                                <circle class="progress" cx="50" cy="50" r="45"
                                    style="stroke-dashoffset: calc(283 - (283 * {$cpu_info.usage_percentage}) / 100);">
                                </circle>
                            </svg>
                            <div class="progress-text">{$cpu_info.usage_percentage}%</div>
                        </div>
                        <div class="ml-3">
                            <h3 class="mb-0">{$cpu_info.model}</h3>
                            <small class="text-muted">{$cpu_info.cores} Cores</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card status-card shadow-lg">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-memory text-success"></i> Memory</h5>
                    <div class="progress-container">
                        <div class="d-flex justify-content-between">
                            <small>Used ({$memory_usage.used} MB)</small>
                            <small>Total {$memory_usage.total} MB</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-gradient-success" role="progressbar"
                                style="width: {$memory_usage.used_percentage}%"
                                aria-valuenow="{$memory_usage.used_percentage}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card status-card shadow-lg">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-database text-danger"></i> Storage</h5>
                    <div class="progress-container">
                        <div class="d-flex justify-content-between">
                            <small>Used ({$disk_usage.used})</small>
                            <small>Total {$disk_usage.total}</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-gradient-danger" role="progressbar"
                                style="width: {$disk_usage.used_percentage}"
                                aria-valuenow="{$disk_usage.used_percentage}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>

    <!-- System Details Table -->
    <div class="card shadow-lg mt-4">
        <div class="text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Details</h5>
        </div>
        <div class="card-body">
            <div class="row system-info">
                {foreach $systemInfo as $key => $value}
                <div class="col-md-4 mb-3">
                    <div class="info-item">
                        <span class="label">{$key}:</span>
                        <span class="value">{$value}</span>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </div>
    <br>
    <!-- Service Status Section -->
    <div class="card shadow-lg mt-4">
        <div class="text-dark">
            <h5 class="mb-0"><i class="fas fa-cogs"></i> Service Status</h5>
        </div>
        <div class="card-body">
            <div class="row">
                {foreach $serviceTable.rows as $row}
                <div class="col-md-6">
                    <div class="service-status-item {if $row.1|strpos:'active' !== false}active{else}inactive{/if}">
                        <div class="service-icon">
                            {if $row.1|strpos:'active' !== false}
                            <i class="fas fa-check-circle text-success"></i>
                            {else}
                            <i class="fas fa-times-circle text-danger"></i>
                            {/if}
                        </div>
                        <div class="service-info">
                            <h6>{$row.0}</h6>
                            <small>{$row.1}</small>
                        </div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </div>

    <div class="card shadow-lg mt-4">
        <div class="text-white">
            <h5 class="mb-0"><i class="fas fa-terminal"></i> Server Controls</h5>
        </div>
        <div class="card-body text-center control-panel">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <form action="{$_url}plugin/system_info" method="post">
                        <input type="hidden" name="reload" value="true">
                        <button type="submit" class="btn btn-lg btn-gradient-info" data-toggle="tooltip"
                            title="Restart FreeRADIUS service" onclick="return confirm('Are you sure?')">
                            <i class="fas fa-sync-alt"></i> Restart RADIUS
                        </button>
                    </form>
                </div>

                <div class="col-md-4">
                    <button class="btn btn-lg btn-gradient-warning" data-toggle="modal" data-target="#rebootModal">
                        <i class="fas fa-redo-alt"></i> Reboot Server
                    </button>
                </div>

                <div class="col-md-4">
                    <button class="btn btn-lg btn-gradient-danger btn-shutdown" data-toggle="modal"
                        data-target="#shutdownModal">
                        <i class="fas fa-power-off"></i> Shutdown
                    </button>
                </div>
            </div>

            <!-- Service Specific Controls -->
            {* <div class="service-controls">
                <h6><i class="fas fa-tools"></i> Service Management</h6>
                <div class="d-flex flex-wrap justify-content-center">
                    {foreach $services as $service}
                    <form action="{$_url}plugin/system_info" method="post">
                        <input type="hidden" name="service" value="{$service}">
                        <button type="submit" class="btn btn-outline-secondary service-btn" data-toggle="tooltip"
                            title="Restart {$service} service" onclick="return confirm('Restart {$service} service?')">
                            <i class="fas fa-redo"></i> {$service}
                        </button>
                    </form>
                    {/foreach}
                </div>
            </div> *}
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="rebootModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning"></i> Confirm Reboot</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to reboot the server? This will interrupt all services!
            </div>
            <div class="modal-footer">
                <form action="{$_url}plugin/system_info" method="post">
                    <input type="hidden" name="reboot" value="true">
                    <button type="submit" class="btn btn-gradient-warning">
                        <i class="fas fa-redo-alt"></i> Confirm Reboot
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shutdownModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Confirm Shutdown</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                WARNING: This will completely shut down the server! Are you absolutely sure?
            </div>
            <div class="modal-footer">
                <form action="{$_url}plugin/system_info" method="post">
                    <input type="hidden" name="shutdown" value="true">
                    <button type="submit" class="btn btn-gradient-danger">
                        <i class="fas fa-power-off"></i> Confirm Shutdown
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<hr>
<br><br><br>
{if $output|@count > 0}
<div class="panel panel-primary panel-hovered panel-stacked mb30">
    <div class="panel-heading">Results</div>
    <div class="panel-body">
        <div class="bs-callout bs-callout-info" id="callout-navbar-role">
            {if $returnCode === 0}
            <p>Freeradius service reloaded successfully!</p>
            {else}
            <p>Freeradius service reload failed. Return code: {$returnCode}</p>
            <ul>
                {foreach from=$output item=item}
                <li>{$item}</li>
                {/foreach}
            </ul>
            {/if}
        </div>
    </div>
</div>
{/if}

<div class="bs-callout bs-callout-info" id="callout-navbar-role">
    <h6>Note:</h6>
    To restart the FreeRADIUS service, you need to run the following command as root:<br />
    www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart freeradius.service

</div>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()

        $('.btn').on('click', function (e) {
            var rippler = $('<span class="ripple"></span>');
            rippler.css({
                left: e.pageX - $(this).offset().left - rippler.width() / 2,
                top: e.pageY - $(this).offset().top - rippler.height() / 2
            });
            $(this).append(rippler);
            setTimeout(() => rippler.remove(), 800);
        });
    });

    $('#shutdownModal, #rebootModal').on('show.bs.modal', function (e) {
        if (!confirm('Are you REALLY sure about this critical operation?')) {
            e.preventDefault();
        }
    });
</script>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        const portalLink = "https://t.me/focuslinkstech";
        const updateLink = "{$updateLink}";
        const productName = '{$productName}';
        const version = '{$version}';
        if (updateLink !== "") {
            $('#version').html(productName + ' | Ver: ' + version + ' | <a href="' + updateLink + '">Update Available</a> | by: <a href="' + portalLink + '">Focuslinks Tech</a>');
        } else {
            $('#version').html(productName + ' | Ver: ' + version + ' | by: <a href="' + portalLink + '">Focuslinks Tech</a>');
        }
    });
</script>

{include file="sections/footer.tpl"}