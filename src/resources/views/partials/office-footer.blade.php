<footer class="mt-8 w-full text-center text-sm text-slate-500">
    <div class="max-w-4xl mx-auto p-4">
        <div class="flex items-center justify-center gap-3 mb-2">
            <img src="{{ asset('img/juris-logo.png') }}" alt="{{ config('juris.office_name') }}" style="height:36px; object-fit:contain;" />
            <div class="text-left">
                <div class="font-semibold">{{ config('juris.office_name') }}</div>
                <div class="text-xs">{{ config('juris.address') }} • {{ config('juris.oab') }}</div>
            </div>
        </div>
        <div class="text-xs">
            <a href="tel:{{ config('juris.phone') }}">{{ config('juris.phone') }}</a> •
            <a href="mailto:{{ config('juris.emails.contact') }}">{{ config('juris.emails.contact') }}</a>
        </div>
    </div>
</footer>
