<?php

namespace App\Contracts;

interface ScopeContext
{
    public function activeScopeId(): ?int;

    public function setActiveScopeId(int $scopeId): void;
}

