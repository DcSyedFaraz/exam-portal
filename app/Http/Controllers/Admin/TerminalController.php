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
        // Linux/macOS: wrap in sh -c so the shell resolves PATH and handles multi-word commands
        if ($isWindows) {
            $shellCmd = 'cmd /c ' . $resolved;
        } else {
            $shellCmd = ['/bin/sh', '-c', $resolved];
        }

        $proc = proc_open(
            $shellCmd,
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

        $output   = '';
        $start    = time();
        $timeout  = 60;
        $exitCode = null;

        while (true) {
            $out = fread($pipes[1], 8192);
            $err = fread($pipes[2], 8192);
            if ($out !== false && $out !== '') $output .= $out;
            if ($err !== false && $err !== '') $output .= $err;

            $status = proc_get_status($proc);
            if (! $status['running']) {
                // Capture exit code NOW, before pipes are closed / proc_close reaps it.
                // On Windows proc_close() often returns -1 after the process has already exited.
                $exitCode = (int) $status['exitcode'];
                break;
            }

            if ((time() - $start) >= $timeout) {
                proc_terminate($proc);
                $output .= "\n⏱ Command timed out after {$timeout}s.\n";
                break;
            }

            usleep(100000);
        }

        // Drain any remaining output after the process ends
        $output .= stream_get_contents($pipes[1]);
        $output .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $closed = proc_close($proc);

        // Prefer the status-captured exit code; fall back to proc_close() only when
        // we never captured one (e.g. timeout path) and proc_close gives a valid code.
        if ($exitCode === null) {
            $exitCode = ($closed >= 0) ? $closed : 1;
        }

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

        // Always inject app env vars so sub-processes (artisan / composer etc.) work correctly
        $base['APP_ENV']      = config('app.env', 'local');
        $base['APP_KEY']      = config('app.key', '');
        $base['APP_URL']      = config('app.url', 'http://localhost');
        $base['DB_CONNECTION'] = config('database.default', 'mysql');
        $base['DB_HOST']      = config('database.connections.mysql.host', '127.0.0.1');
        $base['DB_PORT']      = config('database.connections.mysql.port', '3306');
        $base['DB_DATABASE']  = config('database.connections.mysql.database', 'exam_portal');
        $base['DB_USERNAME']  = config('database.connections.mysql.username', 'root');
        $base['DB_PASSWORD']  = config('database.connections.mysql.password', '');
        $base['SESSION_DRIVER'] = config('session.driver', 'file');

        // Remove keys that can confuse sub-processes (HTTP request headers etc.)
        foreach (['HTTP_HOST', 'REQUEST_URI', 'REQUEST_METHOD', 'QUERY_STRING',
                  'CONTENT_TYPE', 'CONTENT_LENGTH', 'HTTP_ACCEPT', 'HTTP_COOKIE'] as $k) {
            unset($base[$k]);
        }

        return $base;
    }
}
