@props(['action' => 'submit', 'form' => 'form'])

@php
    $siteKey =
        app(\App\Settings\IntegrationSettings::class)->recaptcha_site_key ?: config('services.recaptcha.site_key');
@endphp

@if ($siteKey)
    <input type="hidden" name="g-recaptcha-response">

    @once('recaptcha-script')
        <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}" defer></script>
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('{{ $form }}');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = form.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;

                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ $siteKey }}', {
                        action: '{{ $action }}'
                    }).then(function(token) {
                        form.querySelector('[name="g-recaptcha-response"]').value = token;
                        form.submit();
                    }).catch(function() {
                        if (btn) btn.disabled = false;
                    });
                });
            });
        });
    </script>
@endif
