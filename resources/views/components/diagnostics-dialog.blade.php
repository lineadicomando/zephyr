<div class="space-y-5 text-sm">
    <div class="text-center space-y-1">
        <p class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400">{{ __('credits.technical_information') }}</p>
        <p class="font-bold text-xl text-gray-950 dark:text-white">{{ __('credits.diagnostics') }}</p>
        <p class="text-gray-500 dark:text-gray-400">{{ __('credits.diagnostics_visibility_note') }}</p>
    </div>

    <div>
        <table class="w-full text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                <tr>
                    <td class="py-1.5 pr-6 font-semibold text-gray-700 dark:text-gray-300 w-1/4">Environment</td>
                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-400">{{ $appEnv }}</td>
                </tr>
                <tr>
                    <td class="py-1.5 pr-6 font-semibold text-gray-700 dark:text-gray-300">Domain</td>
                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-400">{{ $domain }}</td>
                </tr>
                <tr>
                    <td class="py-1.5 pr-6 font-semibold text-gray-700 dark:text-gray-300">PHP</td>
                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-400">{{ $phpVersion }}</td>
                </tr>
                <tr>
                    <td class="py-1.5 pr-6 font-semibold text-gray-700 dark:text-gray-300">Database</td>
                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-400">{{ $dbVersion }}</td>
                </tr>
                <tr>
                    <td class="py-1.5 pr-6 font-semibold text-gray-700 dark:text-gray-300">Memory</td>
                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-400">{{ $memoryUsage }} MB</td>
                </tr>
                <tr>
                    <td class="py-1.5 pr-6 font-semibold text-gray-700 dark:text-gray-300">Server</td>
                    <td class="py-1.5 font-mono text-gray-600 dark:text-gray-400 break-all">{{ $serverInfo }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
