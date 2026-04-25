<style>
    .dash-animate > * {
        opacity: 0;
        transform: translateY(16px);
        animation: dashFadeUp .65s cubic-bezier(.22,.61,.36,1) forwards;
    }
    .dash-animate > *:nth-child(1) { animation-delay: .05s; }
    .dash-animate > *:nth-child(2) { animation-delay: .12s; }
    .dash-animate > *:nth-child(3) { animation-delay: .19s; }
    .dash-animate > *:nth-child(4) { animation-delay: .26s; }
    .dash-animate > *:nth-child(5) { animation-delay: .33s; }
    .dash-animate > *:nth-child(6) { animation-delay: .40s; }
    @keyframes dashFadeUp {
        to { opacity: 1; transform: translateY(0); }
    }
    .apexcharts-tooltip {
        box-shadow: 0 10px 30px rgba(0,0,0,.12) !important;
        border-radius: 10px !important;
    }
</style>
