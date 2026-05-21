<div class="box box-solid fnp-analytics-card fnp-analytics-card-sales">
    <div class="box-header">
        <div class="fnp-widget-title">
            <span class="fnp-widget-icon"><i class="fa fa-line-chart"></i></span>
            <div>
                <h3 class="box-title">{Lang::T('Total Monthly Sales')}</h3>
                <p>{Lang::T('Revenue trend across the year')}</p>
            </div>
        </div>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-default btn-sm" data-widget="collapse" title="{Lang::T('Collapse')}"><i class="fa fa-minus"></i></button>
            <a href="{Text::url('dashboard&refresh')}" class="btn btn-default btn-sm" title="{Lang::T('Refresh')}"><i class="fa fa-refresh"></i></a>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <div class="fnp-chart-wrap">
            <canvas class="chart" id="salesChart"></canvas>
        </div>
    </div>
</div>

<script type="text/javascript">
    {if $_c['hide_tmc'] != 'yes'}
        {literal}
            document.addEventListener("DOMContentLoaded", function() {
                var monthlySales = JSON.parse('{/literal}{$monthlySales|json_encode}{literal}');

                var monthNames = [
                    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                ];

                var labels = [];
                var data = [];

                for (var i = 1; i <= 12; i++) {
                    var month = findMonthData(monthlySales, i);
                    labels.push(month ? monthNames[i - 1] : monthNames[i - 1].substring(0, 3));
                    data.push(month ? month.totalSales : 0);
                }

                var ctx = document.getElementById('salesChart').getContext('2d');
                var styles = getComputedStyle(document.documentElement);
                var primary = styles.getPropertyValue('--fnp-primary').trim() || '#41a146';
                var gradient = ctx.createLinearGradient(0, 0, 0, 280);
                gradient.addColorStop(0, 'rgba(65, 161, 70, 0.30)');
                gradient.addColorStop(1, 'rgba(65, 161, 70, 0.02)');
                var chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Monthly Sales',
                            data: data,
                            backgroundColor: gradient,
                            borderColor: primary,
                            borderWidth: 3,
                            pointBackgroundColor: '#f9c02b',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.36
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6b7280'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(107, 114, 128, 0.16)'
                                },
                                ticks: {
                                    color: '#6b7280'
                                }
                            }
                        }
                    }
                });
            });

            function findMonthData(monthlySales, month) {
                for (var i = 0; i < monthlySales.length; i++) {
                    if (monthlySales[i].month === month) {
                        return monthlySales[i];
                    }
                }
                return null;
            }
        {/literal}
    {/if}
</script>
