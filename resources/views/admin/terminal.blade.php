@extends('layouts.app')

@section('title', 'Terminal')
@section('page-title', 'Terminal')
@section('breadcrumb', 'Admin › Terminal')

@section('content')

{{-- Quick-command buttons --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach([
        'php artisan --version'             => 'Artisan ver',
        'php artisan route:list'            => 'Routes',
        'php artisan migrate:status'        => 'Migrations',
        'php artisan optimize:clear'        => 'Clear all caches',
        'php artisan cache:clear'           => 'Clear cache',
        'php artisan config:clear'          => 'Clear config',
        'php artisan view:clear'            => 'Clear views',
        'php artisan storage:link'          => 'Storage link',
        'php artisan queue:work --once'     => 'Queue once',
        'composer dump-autoload'            => 'Dump autoload',
        'composer install --no-interaction' => 'Composer install',
        'npm run build'                     => 'NPM build',
        'npm install'                       => 'NPM install',
    ] as $cmd => $label)
    <button type="button"
            onclick="setCommand({{ json_encode($cmd) }})"
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-800 text-gray-300 hover:bg-yellow-400 hover:text-gray-900 transition-colors">
        {{ $label }}
    </button>
    @endforeach
</div>

{{-- Terminal card --}}
<div class="bg-gray-950 rounded-2xl shadow-xl overflow-hidden border border-gray-800">

    {{-- Title bar --}}
    <div class="flex items-center gap-2 px-4 py-3 bg-gray-900 border-b border-gray-800">
        <span class="w-3 h-3 rounded-full bg-red-500"></span>
        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
        <span class="w-3 h-3 rounded-full bg-green-500"></span>
        <span class="ml-3 text-xs text-gray-500 font-mono">admin@exam-portal — {{ base_path() }}</span>
        <button onclick="clearOutput()" class="ml-auto text-xs text-gray-600 hover:text-gray-400 transition-colors">clear</button>
    </div>

    {{-- Output --}}
    <div id="output"
         class="font-mono text-sm text-green-400 p-5 min-h-64 max-h-[60vh] overflow-y-auto whitespace-pre-wrap break-all leading-relaxed">
        <span class="text-gray-600">Welcome to the Admin Terminal. Type a command below and press Enter or click Run.
Allowed prefixes: php artisan, composer, npm, node, k6, git, dir, ping, curl
</span>
    </div>

    {{-- Input --}}
    <div class="border-t border-gray-800 px-4 py-3 flex items-center gap-3">
        <span class="text-yellow-400 font-mono text-sm shrink-0">$</span>
        <input id="cmd-input"
               type="text"
               placeholder="php artisan inspire"
               autocomplete="off"
               spellcheck="false"
               class="flex-1 bg-transparent text-green-300 font-mono text-sm outline-none placeholder-gray-700 caret-yellow-400">
        <button id="run-btn"
                onclick="runCommand()"
                class="shrink-0 px-4 py-1.5 rounded-lg bg-yellow-400 text-gray-900 text-sm font-semibold hover:bg-yellow-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            Run
        </button>
        <button onclick="clearOutput()"
                class="shrink-0 px-3 py-1.5 rounded-lg bg-gray-800 text-gray-400 text-sm hover:bg-gray-700 transition-colors">
            Clear
        </button>
    </div>
</div>

<p class="text-xs text-gray-400 mt-3">
    ⚠️ Only whitelisted command prefixes are permitted. Shell chaining (<code class="bg-gray-100 px-1 rounded">; || & `</code>) is blocked.
</p>

<script>
const csrfToken = '{{ csrf_token() }}';
const runUrl    = '{{ route('admin.terminal.run') }}';
const output    = document.getElementById('output');
const input     = document.getElementById('cmd-input');
const runBtn    = document.getElementById('run-btn');

const history   = [];
let histIdx     = -1;

function append(text, cls = '') {
    const span = document.createElement('span');
    if (cls) span.className = cls;
    span.textContent = text;
    output.appendChild(span);
    output.scrollTop = output.scrollHeight;
}

function clearOutput() {
    output.innerHTML = '';
}

function setCommand(cmd) {
    input.value = cmd;
    input.focus();
}

async function runCommand() {
    const cmd = input.value.trim();
    if (!cmd) return;

    history.unshift(cmd);
    histIdx = -1;

    append(`\n$ ${cmd}\n`, 'text-yellow-400');
    input.value = '';
    runBtn.disabled = true;
    runBtn.textContent = 'Running…';

    try {
        const res = await fetch(runUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ command: cmd }),
        });

        const data = await res.json();
        const exitOk = data.exit === 0 || data.exit === null;
        append(data.output, exitOk ? 'text-green-400' : 'text-red-400');

        if (!exitOk) {
            append(`\n[exit code: ${data.exit}]\n`, 'text-red-500');
        }
    } catch (err) {
        append(`\nNetwork error: ${err.message}\n`, 'text-red-400');
    } finally {
        runBtn.disabled = false;
        runBtn.textContent = 'Run';
        input.focus();
    }
}

// Enter to run, Up/Down for history
input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        runCommand();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (histIdx < history.length - 1) {
            histIdx++;
            input.value = history[histIdx];
        }
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (histIdx > 0) {
            histIdx--;
            input.value = history[histIdx];
        } else {
            histIdx = -1;
            input.value = '';
        }
    }
});

// Auto-focus on load
input.focus();
</script>
@endsection
