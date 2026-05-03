<?php

namespace App\Support\Scope;

use App\Contracts\ScopeContext;
use Illuminate\Contracts\Session\Session;

final class SessionScopeContext implements ScopeContext
{
    private const SESSION_KEY = 'active_scope_id';

    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function activeScopeId(): ?int
    {
        $scopeId = $this->session->get(self::SESSION_KEY);

        if (! is_numeric($scopeId)) {
            return null;
        }

        return (int) $scopeId;
    }

    public function setActiveScopeId(int $scopeId): void
    {
        $this->session->put(self::SESSION_KEY, $scopeId);
    }
}

