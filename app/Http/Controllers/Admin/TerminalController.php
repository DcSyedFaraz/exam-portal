<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TerminalController extends Controller
{
    private const ALLOWED = [
        'php artisan',
        'composer',
        'npm',
        'node',
        'k6',
        'git',
        'ls',
        'dir',
        'cat',
        'tail',
        'head',
        'grep',
        'ping',
        'curl',
        'mysql',
    ];

    /** Windows-specific binary path map */
    private const WIN_BIN = [
        'php'      => ['E:\\laragon\\bin\\php\\php8.3\\php.exe'],
        'composer' => ['E:\\laragon\\bin\\composer\\composer.bat', 'E:\\laragon\\bin\\composer\\composer'],
        'npm'      => ['C:\\Program Files\\nodejs\\npm.cmd', 'C:\\Program Files\\nodejs\\npm'],
        'node'     => ['C:\\Program Files\\nodejs\\node.exe'],
    ];

    /** Linux/macOS candidate paths for binaries not always on Apache's PATH */
    private const UNIX_BIN = [
        'php'      => ['/usr/bin/php', '/usr/local/bin/php'],
        'composer' => ['/usr/local/bin/composer', '/usr/bin/composer'],
        'npm'      => ['/usr/local/bin/npm', '/usr/bin/npm'],
        'node'     => ['/usr/local/bin/node', '/usr/bin/node'],
    ];

    public function index(): View
    {
        return view('admin.terminal');
    }

    public function run(Request $request): JsonResponse
    {
        $request->validate(['command' => ['required', 'string', 'max:500']]);

        $command = trim($request->input('command'));

        if (! $this->isAllowed($command)) {
            return response()->json([
                'output' => "❌ Command not permitted.\nAllowed prefixes: " . implode(', ', self::ALLOWED) . "\n",
                'exit'   => 1,
            ], 403);
        }

        $isWindows = PHP_OS_FAMILY === 'Windows';
        $resolved  = $isWindows
            ? $this->resolveWindows($command)
            : $this->resolveUnix($command);

        $cwd = base_path();

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = $this->buildEnv($isWindows);

        // Windows needs cmd /c to handle .cmd/.bat files
        $proc = proc_open(
            $isWindows ? 'cmd /c ' . $resolved : $resolved,
            $descriptors,
            $pipes,
            $cwd,
            $env
        );

        if (! is_resource($proc)) {
            return response()->json(['output' => "Failed to start process.\n", 'exit' => 1], 500);
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output  = '';
        $start   = time();
        $timeout = 60;

        while (true) {
            $out = fread($pipes[1], 8192);
            $err = fread($pipes[2], 8192);
            if ($out !== false && $out !== '') $output .= $out;
            if ($err !== false && $err !== '') $output .= $err;

            $status = proc_get_status($proc);
            if (! $status['running']) break;

            if ((time() - $start) >= $timeout) {
                proc_terminate($proc);
                $output .= "\n⏱ Command timed out after {$timeout}s.\n";
                break;
            }

            usleep(100000);
        }

        $output .= stream_get_contents($pipes[1]);
        $output .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($proc);

        // Strip ANSI colour codes and normalize line endings
        $output = preg_replace('/\x1B\[[0-9;]*[mGKHF]/u', '', $output);
        $output = str_replace("\r\n", "\n", $output);

        return response()->json([
            'output' => $output !== '' ? $output : "(no output)\n",
            'exit'   => $exitCode,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function isAllowed(string $command): bool
    {
        // Block shell chaining / dangerous characters
        if (preg_match('/[;&`]|>>|\|\|/', $command)) {
            return false;
        }

        $lower = strtolower($command);
        foreach (self::ALLOWED as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolveWindows(string $command): string
    {
        foreach (self::WIN_BIN as $name => $candidates) {
            if (str_starts_with(strtolower($command), $name . ' ') || strtolower($command) === $name) {
                foreach ($candidates as $path) {
                    if (file_exists($path)) {
                        return '"' . $path . '"' . substr($command, strlen($name));
                    }
                }
            }
        }

        return $command;
    }

    private function resolveUnix(string $command): string
    {
        foreach (self::UNIX_BIN as $name => $candidates) {
            if (str_starts_with(strtolower($command), $name . ' ') || strtolower($command) === $name) {
                // First try: let the shell find it via PATH (works if Apache has a proper PATH)
                $which = shell_exec("which {$name} 2>/dev/null");
                if ($which) {
                    $path = trim($which);
                    return $path . substr($command, strlen($name));
                }
                // Fallback: try known candidate paths
                foreach ($candidates as $path) {
                    if (file_exists($path)) {
                        return $path . substr($command, strlen($name));
                    }
                }
            }
        }

        return $command;
    }

    private function buildEnv(bool $isWindows): array
    {
        $base = array_merge($_SERVER, $_ENV);

        if ($isWindows) {
            $extraPath = implode(';', [
                'E:\\laragon\\bin\\php\\php8.3',
                'E:\\laragon\\bin\\composer',
                'C:\\Program Files\\nodejs',
                'C:\\Windows\\System32',
                'C:\\Windows',
            ]);
            $base['PATH']    = $extraPath . (isset($base['PATH']) ? ';' . $base['PATH'] : '');
            $base['COMSPEC'] = 'C:\\Windows\\System32\\cmd.exe';
        } else {
            $extraPath = '/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin';
            $base['PATH'] = $extraPath . (isset($base['PATH']) ? ':' . $base['PATH'] : '');
        }

        // Always inject app env vars so sub-processes (artisan etc.) work correctly
        $base['APP_ENV'] = config('app.env', 'local');
        $base['APP_KEY'] = config('app.key', '');
        $base['DB_CONNECTION'] = config('database.default', 'mysql');

        return $base;
    }
}
