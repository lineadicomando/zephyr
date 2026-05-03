<div class="space-y-5 text-sm">
    <div class="text-center space-y-1">
        <p class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400">{{ __('credits.about') }}</p>
        <p class="font-bold text-xl text-gray-950 dark:text-white">{{ $project['name'] ?? 'Project' }}</p>
        <p class="text-gray-500 dark:text-gray-400">{{ __('credits.based_on_version', ['projectName' => $project['name'] ?? 'Project', 'ver' => $project['version'] ?? 'n/a']) }}</p>
    </div>

    <div>
        <p class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('credits.author') }}</p>
        <p class="text-gray-700 dark:text-gray-300">{{ __('credits.author_name', ['projectName' => $project['name'] ?? 'Project', 'authorName' => $project['author_name'] ?? 'Project Maintainers']) }}</p>
        @if(!empty($project['author_email']))
            <p class="text-gray-700 dark:text-gray-300">{!! __('credits.author_email', ['authorEmail' => $project['author_email']]) !!}</p>
        @endif
    </div>

    <div>
        <p class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('credits.license') }}</p>
        <p class="text-gray-700 dark:text-gray-300">{!! __('credits.license_text', ['projectName' => $project['name'] ?? 'Project', 'license' => $project['license'] ?? 'Proprietary']) !!}</p>
    </div>

    <div>
        <p class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('credits.disclaimer') }}</p>
        <p class="text-gray-700 dark:text-gray-300">{!! __('credits.disclaimer_text', ['projectName' => $project['name'] ?? 'Project', 'authorName' => $project['author_name'] ?? 'Project Maintainers']) !!}</p>
    </div>
</div>
