<div class="panel panel-info panel-hovered mb20 activities fnp-analytics-card fnp-insight-card">
    <div class="panel-heading">
        <div class="fnp-widget-title">
            <span class="fnp-widget-icon"><i class="fa fa-pie-chart"></i></span>
            <div>
                <h3 class="box-title">{Lang::T('All Users Insights')}</h3>
                <p>{Lang::T('Active, expired, and inactive customers')}</p>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <div class="fnp-chart-wrap fnp-donut-wrap">
            <canvas id="userRechargesChart"></canvas>
        </div>
    </div>
</div>


<script type="text/javascript">
    {literal}
        document.addEventListener("DOMContentLoaded", function() {
            // Get the data from PHP and assign it to JavaScript variables
            var u_act = '{/literal}{$u_act}{literal}';
            var c_all = '{/literal}{$c_all}{literal}';
            var u_all = '{/literal}{$u_all}{literal}';
            //lets calculate the inactive users as reported
            var expired = u_all - u_act;
            var inactive = c_all - u_all;
            if (inactive < 0) {
                inactive = 0;
            }
            var styles = getComputedStyle(document.documentElement);
            var primary = styles.getPropertyValue('--fnp-primary').trim() || '#41a146';
            var danger = styles.getPropertyValue('--fnp-danger').trim() || '#dc3545';
            var info = styles.getPropertyValue('--fnp-info').trim() || '#0ea5e9';

            // Create the chart data
            var data = {
                labels: ['Active Users', 'Expired Users', 'Inactive Users'],
                datasets: [{
                    label: 'User Recharges',
                    data: [parseInt(u_act), parseInt(expired), parseInt(inactive)],
                    backgroundColor: [primary, danger, info],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            };

            // Create chart options
            var options = {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            };

            // Get the canvas element and create the chart
            var ctx = document.getElementById('userRechargesChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: options
            });
        });
    {/literal}
</script>
