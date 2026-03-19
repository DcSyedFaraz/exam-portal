@if(session('success') || session('error') || session('info'))
<div id="toast-message"
     class="fixed top-4 right-4 z-50 max-w-sm w-full shadow-lg rounded-xl px-5 py-4 flex items-start gap-3 transition-all duration-300
     @if(session('success')) bg-green-500 text-white
     @elseif(session('error')) bg-red-500 text-white
     @else bg-blue-500 text-white
     @endif">
    <div class="shrink-0 mt-0.5">
        @if(session('success'))
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        @elseif(session('error'))
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @endif
    </div>
    <p class="text-sm font-medium">
        {{ session('success') ?? session('error') ?? session('info') }}
    </p>
</div>
@endif
