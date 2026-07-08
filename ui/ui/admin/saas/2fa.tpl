{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    {if !$settings.sms_ready}
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            Configure TALKSASA or another SMS gateway before enabling SuperAdmin 2FA. FASTNETPAY will not enable OTP if SMS cannot send.
        </div>
    {/if}

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-shield"></i> SuperAdmin SMS 2FA</h3>
        </div>
        <div class="box-body">
            <p class="text-muted">2FA is SMS-only for now. It applies only to SuperAdmin accounts and logs every OTP issue, failure, and successful verification.</p>
            <div class="table-responsive">
                <table class="table table-hover fnp-compact-table">
                    <thead>
                        <tr>
                            <th>SuperAdmin</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $superadmins as $sa}
                            {assign var=enabled value=SaasBilling::superAdmin2FAEnabled($sa.id)}
                            <tr>
                                <td><strong>{$sa.username|escape}</strong><br><small>{$sa.fullname|escape}</small></td>
                                <td>{if $sa.phone}{$sa.phone|escape}{else}<span class="text-danger">Missing phone</span>{/if}</td>
                                <td>{$sa.email|escape}</td>
                                <td>
                                    {if $enabled}
                                        <span class="label label-success">Enabled</span>
                                    {else}
                                        <span class="label label-default">Disabled</span>
                                    {/if}
                                </td>
                                <td class="text-right">
                                    <form method="post" action="{Text::url('saas/2fa-save')}" class="fnp-inline-action">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <input type="hidden" name="admin_id" value="{$sa.id}">
                                        <input type="hidden" name="enabled" value="{if $enabled}0{else}1{/if}">
                                        <button class="btn btn-{if $enabled}default{else}success{/if} btn-sm" type="submit" {if !$settings.sms_ready && !$enabled}disabled{/if}>
                                            {if $enabled}<i class="fa fa-unlock"></i> Disable{else}<i class="fa fa-lock"></i> Enable{/if}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
