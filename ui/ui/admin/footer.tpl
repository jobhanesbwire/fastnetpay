</section>
</div>
<footer class="main-footer">
    {if !($_tenant_mode eq 'tenant' && $_admin['user_type'] neq 'SuperAdmin')}
        <div class="pull-right" id="version" onclick="location.href = '{Text::url('community')}#latestVersion';"></div>
    {/if}
    <div class="fnp-footer-content">
        <div class="fnp-footer-main">
            {if $_c['fastnetpay_footer_text'] neq ''}
                <span>{Lang::htmlspecialchars($_c['fastnetpay_footer_text'])}</span>
            {else}
                <span>FASTNETPAY ISP Billing Platform</span>
            {/if}
            {if $_c['fastnetpay_footer_html'] neq ''}
                <span class="fnp-footer-html">{Text::safeHtml($_c['fastnetpay_footer_html'])}</span>
            {/if}
        </div>
        <div class="fnp-footer-meta">
            {if $_c['fastnetpay_footer_copyright'] neq ''}
                <span>{Lang::htmlspecialchars($_c['fastnetpay_footer_copyright'])}</span>
            {else}
                <span>&copy; {date('Y')} {if $_c['fastnetpay_footer_company'] neq ''}{Lang::htmlspecialchars($_c['fastnetpay_footer_company'])}{else}{Lang::htmlspecialchars($_c['CompanyName'])}{/if}</span>
            {/if}
            {if $_c['fastnetpay_footer_company_url'] neq '' && Text::safeUrl($_c['fastnetpay_footer_company_url']) neq ''}
                <a href="{Text::safeUrl($_c['fastnetpay_footer_company_url'])}" rel="nofollow noreferrer noopener" target="_blank">
                    {if $_c['fastnetpay_footer_company'] neq ''}{Lang::htmlspecialchars($_c['fastnetpay_footer_company'])}{else}{Lang::htmlspecialchars($_c['CompanyName'])}{/if}
                </a>
            {/if}
            {if $_c['fastnetpay_footer_support_email'] neq ''}
                <a href="mailto:{Lang::htmlspecialchars($_c['fastnetpay_footer_support_email'])}">
                    {Lang::htmlspecialchars($_c['fastnetpay_footer_support_email'])}
                </a>
            {/if}
            {if $_c['fastnetpay_footer_powered'] neq 'hide'}
                <span class="fnp-powered">Based on <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
                    target="_blank">PHPNuxBill</a> by iBNuX, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
                    target="_blank">AdminLTE</a></span>
            {/if}
        </div>
    </div>
</footer>
</div>
<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/adminlte.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/plugins/select2.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/pace.min.js"></script>
<script src="{$app_url}/ui/ui/summernote/summernote.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/custom.js?2025.2.5"></script>
<script src="{$app_url}/ui/ui/scripts/fastnetpay-ui.js?2026.5.21"></script>

{if isset($xfooter)}
    {$xfooter}
{/if}
{literal}
    <script>
        var listAttApi;
        var posAttApi = 0;
        $(document).ready(function() {
            $('.select2').select2({theme: "bootstrap"});
            $('.select2tag').select2({theme: "bootstrap", tags: true});
            var listAtts = document.querySelectorAll(`button[type="submit"]`);
            listAtts.forEach(function(el) {
                if (el.addEventListener) { // all browsers except IE before version 9
                    el.addEventListener("click", function() {
                        var txt = $(this).html();
                        $(this).html(
                            `<span class="loading"></span>`
                        );
                        setTimeout(() => {
                            $(this).prop("disabled", true);
                        }, 100);
                        setTimeout(() => {
                            $(this).html(txt);
                            $(this).prop("disabled", false);
                        }, 5000);
                    }, false);
                } else {
                    if (el.attachEvent) { // IE before version 9
                        el.attachEvent("click", function() {
                            var txt = $(this).html();
                            $(this).html(
                                `<span class="loading"></span>`
                            );
                            setTimeout(() => {
                                $(this).prop("disabled", true);
                            }, 100);
                            setTimeout(() => {
                                $(this).html(txt);
                                $(this).prop("disabled", false);
                            }, 5000);
                        });
                    }
                }

            });
            setTimeout(() => {
                listAttApi = document.querySelectorAll(`[api-get-text]`);
                apiGetText();
            }, 500);
        });

        function ask(field, text){
            var txt = field.innerHTML;
            if (confirm(text)) {
                setTimeout(() => {
                    field.innerHTML = field.innerHTML.replace(`<span class="loading"></span>`, txt);
                    field.removeAttribute("disabled");
                }, 5000);
                return true;
            } else {
                setTimeout(() => {
                    field.innerHTML = field.innerHTML.replace(`<span class="loading"></span>`, txt);
                    field.removeAttribute("disabled");
                }, 500);
                return false;
            }
        }

        function apiGetText(){
            var el = listAttApi[posAttApi];
            if(el != undefined){
                $.get(el.getAttribute('api-get-text'), function(data) {
                    el.innerHTML = data;
                    posAttApi++;
                    if(posAttApi < listAttApi.length){
                        apiGetText();
                    }
                });
            }
        }

        function setKolaps() {
            var kolaps = getCookie('kolaps');
            if (kolaps) {
                setCookie('kolaps', false, 30);
            } else {
                setCookie('kolaps', true, 30);
            }
            return true;
        }

        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })
        $("[data-toggle=popover]").popover();
    </script>
{/literal}

</body>

</html>
