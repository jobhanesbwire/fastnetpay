<div class="box box-primary box-solid fnp-clients-table-box">
    <div class="box-body no-padding table-responsive">
        <table class="table table-bordered table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Payment</th>
                    <th>Subtotal</th>
                    <th>Discount</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                {foreach $sales as $sale}
                    <tr>
                        <td><span class="fnp-mono">{$sale['sale_number']}</span></td>
                        <td>{$sale['created_at']}</td>
                        <td>{if $sale['customer_id']}#{$sale['customer_id']}{else}Walk-in{/if}</td>
                        <td>{$sale['payment_method']|capitalize}</td>
                        <td>{Lang::moneyFormat($sale['subtotal'])}</td>
                        <td>{Lang::moneyFormat($sale['discount'])}</td>
                        <td><strong>{Lang::moneyFormat($sale['total'])}</strong></td>
                        <td><span class="label label-success">{$sale['status']|capitalize}</span></td>
                        <td>{$sale['notes']}</td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="9" class="text-center text-muted fnp-empty-row">No POS sales found.</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    <div class="box-footer">{include file="pagination.tpl"}</div>
</div>
