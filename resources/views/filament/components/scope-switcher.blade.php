@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Scope> $scopes */
@endphp

@if($scopes->isNotEmpty())
    <form method="POST" action="{{ route('scopes.switch', ['scope' => $activeScopeId ?? $scopes->first()->id]) }}" class="fi-topbar-scope-switcher">
        @csrf
        <select
            id="topbar-scope-switch"
            name="scope_id"
            aria-label="{{ __('Switch scope') }}"
            class="fi-input rounded-lg text-sm h-9 min-w-[13rem] pr-8"
            onchange="if (this.value) { this.form.action = '/scopes/' + this.value + '/switch'; this.form.submit(); }"
        >
            @foreach($scopes as $scope)
                <option value="{{ $scope->id }}" @selected((int) $scope->id === (int) $activeScopeId)>
                    {{ $scope->name }} ({{ $scope->type }})
                </option>
            @endforeach
        </select>
    </form>
@endif
