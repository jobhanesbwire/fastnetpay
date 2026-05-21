{include file="sections/header.tpl"}

<div class="fnp-error-inline">
    <main class="fnp-error-card">
        <section class="fnp-error-top">
            <div class="fnp-error-illustration" aria-hidden="true">
                <span></span><span></span><span></span>
                <span></span><span></span><span></span>
            </div>
            <span class="fnp-error-icon"><i class="fa fa-map-signs"></i></span>
            <h1>404</h1>
        </section>
        <section class="fnp-error-body">
            <div class="fnp-error-message">
                {Lang::T("Oops! The page you are looking for was not found")}.
            </div>
            <div class="fnp-error-actions">
                <a href="javascript:history.back()" onclick="history.back(); return false;" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> {Lang::T('Go Back')}
                </a>
                <a href="{Text::url('dashboard')}" class="btn btn-primary">
                    <i class="fa fa-dashboard"></i> {Lang::T("Back to Dashboard")}
                </a>
                <a href="javascript:location.reload()" onclick="location.reload(); return false;" class="btn btn-warning">
                    <i class="fa fa-refresh"></i> {Lang::T('Reload')}
                </a>
                {if $_c['fastnetpay_footer_support_email'] neq ''}
                    <a href="mailto:{Lang::htmlspecialchars($_c['fastnetpay_footer_support_email'])}" class="btn btn-info">
                        <i class="fa fa-envelope"></i> {Lang::T('Contact Support')}
                    </a>
                {/if}
            </div>
        </section>
    </main>
</div>

{include file="sections/footer.tpl"}
