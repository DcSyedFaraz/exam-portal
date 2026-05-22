{{-- Running ticker banner — shown only for parents --}}
@if(auth()->check() && auth()->user()->hasRole('parent'))
<div id="parent-ticker" class="overflow-hidden bg-gradient-to-r from-yellow-400 via-amber-400 to-yellow-400 text-gray-900 text-sm font-medium mb-4 rounded-xl shadow-sm">

    {{-- Ticker track --}}
    <div class="ticker-track flex whitespace-nowrap py-2.5">
        <span class="ticker-content inline-block pl-4">
            🤔📱 <strong>Mzazi Mpendwa!</strong> &mdash;
            Watoto wetu wanatumia muda mwingi kwenye simu, lakini <strong>tunaweza kubadili jinsi wanavyozitumia!</strong>
            &nbsp;&bull;&nbsp;
            ✅ <strong>Udhibiti Rahisi:</strong> Mpe simu afanye mtihani kwenye mfumo wetu.
            &nbsp;&bull;&nbsp;
            ✅ <strong>Ripoti ya Papo kwa Papo:</strong> Ona alama na maendeleo yake moja kwa moja.
            &nbsp;&bull;&nbsp;
            🎓✨ Mgeuze mtoto wako kuwa "Mwanafunzi wa Kidijitali" &mdash; Geukia mfumo wetu sasa!
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </span>
    </div>
</div>

<style>
    .ticker-track {
        animation: ticker-scroll 30s linear infinite;
    }
    .ticker-track:hover {
        animation-play-state: paused;
    }
    @keyframes ticker-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
</style>

<script>
    (function () {
        const track = document.querySelector('#parent-ticker .ticker-track');
        if (!track) return;
        const content = track.querySelector('.ticker-content');
        track.appendChild(content.cloneNode(true));
    })();
</script>
@endif
