@php
    use App\Services\Analytics\AnalyticsSettings;
    $__show = AnalyticsSettings::enabled() && (bool) AnalyticsSettings::get('consent_required') && ! auth()->check();
@endphp
@if ($__show)
    <div id="wr-consent" role="dialog" aria-live="polite" aria-label="{{ __('analytics.consent.title') }}" hidden
         style="position:fixed;inset-inline:1rem;bottom:1rem;z-index:80;max-width:34rem;margin-inline:auto;background:#fff;border:1px solid #d9d2bd;border-radius:1rem;box-shadow:0 12px 40px rgba(0,0,0,.2);padding:1rem 1.2rem;color:#1c2a24">
        <strong style="display:block;margin-bottom:.3rem;color:#14382a">{{ __('analytics.consent.title') }}</strong>
        <p style="margin:0 0 .8rem;font-size:.9rem;line-height:1.55">{{ __('analytics.consent.text') }}</p>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            <button type="button" data-wr-consent="granted" style="background:#14382a;color:#fff;border-radius:.6rem;padding:.5rem 1.1rem;font-weight:600">{{ __('analytics.consent.accept') }}</button>
            <button type="button" data-wr-consent="denied" style="border:1px solid #14382a;color:#14382a;background:#fff;border-radius:.6rem;padding:.5rem 1.1rem;font-weight:600">{{ __('analytics.consent.decline') }}</button>
        </div>
    </div>
    {{-- Show the banner in the FIRST paint (before the analytics script loads) when no choice is stored: a banner that only appears after
         JavaScript has run becomes the page's Largest Contentful Paint and made the phone LCP several seconds. The analytics script takes over afterwards. --}}
    <script>(function(){try{if(!/(?:^|; )wr_consent=/.test(document.cookie)){document.getElementById('wr-consent').hidden=false;}}catch(e){}})();</script>
    <button type="button" id="wr-consent-open" hidden style="position:fixed;inset-inline-start:1rem;bottom:1rem;z-index:70;background:#14382a;color:#fff;border-radius:999px;padding:.35rem .8rem;font-size:.75rem;opacity:.85">{{ __('analytics.consent.reopen') }}</button>
@endif
