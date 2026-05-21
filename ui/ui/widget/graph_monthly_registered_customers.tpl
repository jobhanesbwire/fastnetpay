<div class="box box-solid fnp-analytics-card fnp-analytics-card-registered">
    <div class="box-header">
        <div class="fnp-widget-title">
            <span class="fnp-widget-icon"><i class="fa fa-user-plus"></i></span>
            <div>
                <h3 class="box-title">{Lang::T('Monthly Registered Customers')}</h3>
                <p>{Lang::T('New customer signups by month')}</p>
            </div>
        </div>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-default btn-sm" data-widget="collapse" title="{Lang::T('Collapse')}"><i class="fa fa-minus"></i></button>
            <a href="{Text::url('dashboard&refresh')}" class="btn btn-default btn-sm" title="{Lang::T('Refresh')}"><i class="fa fa-refresh"></i></a>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <div class="fnp-chart-wrap">
            <canvas class="chart" id="chart"></canvas>
        </div>
    </div>
</div>


<script type="text/javascript">
    {literal}
        document.addEventListener("DOMContentLoaded", function() {
            var counts = JSON.parse('{/literal}{$monthlyRegistered|json_encode}{literal}');

            var monthNames = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            var labels = [];
            var data = [];

            for (var i = 1; i <= 12; i++) {
                var month = counts.find(count => count.date === i);
                labels.push(month ? monthNames[i - 1] : monthNames[i - 1].substring(0, 3));
                data.push(month ? month.count : 0);
            }

            var ctx = document.getElementById('chart').getContext('2d');
            var styles = getComputedStyle(document.documentElement);
            var primary = styles.getPropertyValue('--fnp-primary').trim() || '#41a146';
            var secondary = styles.getPropertyValue('--fnp-secondary').trim() || '#f9c02b';
            var info = styles.getPropertyValue('--fnp-info').trim() || '#0ea5e9';
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Registered Members',
                        data: data,
                        backgroundColor: labels.map(function(_, index) {
                            return index % 3 === 0 ? primary : (index % 3 === 1 ? secondary : info);
                        }),
                        borderColor: 'rgba(255,255,255,0.8)',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 34
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
                                color: '#6b7280',
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    {/literal}
</script>
