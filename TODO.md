# Roadmap

## v0.2.0 Features

Order: item 0 first; items 1 and 2 in sequence; MCP phase 1 (read-only) can run in parallel with items 1 and 2, MCP phase 2 (write) after them.

### 0. Shared Groundwork (API and MCP)
**Domain logic**
- [ ] Move the business logic out of the Filament actions and pages into services/actions shared by panel, API and MCP: inventory, tasks, checklist reports (existing ones: `app/Services/Checklists`, `Reorders`, `Stocks`)
- [ ] Add Form Requests or shared validation rules for the written models (today only products have them)

**Scope outside the panel**
- [ ] Shared scope handling (middleware or trait) for API and MCP: take the scope as an explicit parameter, check it with `ScopeAccessResolver::canAccessScope()`, call `setPermissionsTeamId()`, filter every query by `scope_id` (API and MCP requests have no tenant, see `docs/architecture/global-scopes.md`)

**Authentication**
- [ ] Naming convention for the Sanctum abilities in `config/sanctum.php` (`<resource>:read|write`, `mcp:read|write`)
- [ ] One service user per client (Ansible, each agent), with minimal roles and only the scopes it needs (never `super_admin`/root)
- [ ] Token expiration (`sanctum.expiration`); tokens managed with the existing `api-tokens:*` commands

**Security and deployment**
- [ ] HTTPS only at the reverse proxy; set `TRUSTED_PROXIES` and an `https` `APP_URL`
- [ ] Rate limiting on the API and MCP routes (`throttle`) and at the reverse proxy
- [ ] Audit log of the API and MCP calls (user, token, operation, arguments, scope, outcome)

**Tests**
- [ ] Pest tests for the shared scope handling: a token cannot read or write data of unassigned scopes

### 1. Ansible API: Products/Inventory Synchronization
- [ ] Integrate lineadicomando.zephyr collection (shared with item 2): it defines the API contract
- [ ] Synchronize products table (`/api/products` exists: check it covers the collection needs)
- [ ] Synchronize inventory table (no API endpoints yet: add controller and API Resource on top of the item 0 services and validation)
- [ ] Pest tests for the endpoints, including scope isolation and missing abilities

### 2. Ansible API: Maintenance Tasks Synchronization
- [ ] Synchronize tasks table (no API endpoints yet)
- [ ] Transmit completed maintenance task reports
- [ ] Pest tests for the endpoints, including scope isolation and missing abilities

### 3. MCP Server for AI Agents
Expose the application functions to AI agents through `laravel/mcp`, over Streamable HTTP at `https://zephyr.cmdln.it/mcp`. Builds on item 0.

**Server**
- [ ] Require `laravel/mcp` as a direct dependency with a pinned version (it is only installed as a Laravel Boost dependency)
- [ ] Create the server (`php artisan make:mcp-server`) and register it in `routes/ai.php` with `Mcp::web('/mcp', ...)`, behind the item 0 scope handling
- [ ] Authorize every tool through the existing policies (`Gate::authorize`)
- [ ] Phase 1, read-only tools (needs item 0 only): product search, stock availability, tasks and checklists lookup, reorder proposals; reuse the API Resources for the output
- [ ] Phase 2, write tools, one at a time (after items 1 and 2): register movement, create/update task, fill checklist, create reorder order
- [ ] Resources for the lookup data (types, statuses, locations, positions)
- [ ] Annotate tools with `IsReadOnly` / `IsDestructive`; no delete tools, no management of users, roles and scopes

**Authentication**
- [ ] `auth:sanctum` middleware with the `mcp:read` and `mcp:write` abilities
- [ ] Optional, later: OAuth 2.1 (`Mcp::oauthRoutes()`, requires `laravel/passport`) for the claude.ai/ChatGPT connectors
- [ ] Document the alternative without web exposure: stdio over SSH (`docker compose exec app php artisan mcp:start ...`)

**Security and deployment**
- [ ] Optional IP allowlist or mTLS on `/mcp` at the reverse proxy
- [ ] Validate the `Origin` header (DNS rebinding, required by the MCP specification)
- [ ] Streaming (SSE): `proxy_buffering off` on the reverse proxy, `fastcgi_buffering off` for `/mcp` in `docker/nginx/nginx.conf`, suitable timeouts
- [ ] Mitigate prompt injection from stored data (notes, descriptions): least privilege, confirmation on destructive tools

**Tests and documentation**
- [ ] Pest tests for every tool, including scope isolation and missing abilities/permissions
- [ ] README: MCP setup, token creation, client configuration

---

## v0.3.0 Features

### 1. PWA - Initial Support (without notifications)
- [ ] Implement initial PWA support (web app manifest, icons, service worker)
- [ ] Service worker caching: no cache of authenticated pages and Livewire responses, invalidation of the Vite assets on update
- [ ] Disable notifications

## Future (unscheduled)

Items not assigned to a version yet: move each one to a specific version before implementing it.

### Ansible Job Runner: Predefined Tasks Executed by the Control Node
A service on the Ansible control node polls Zephyr for the jobs to run, runs them and sends back a report. Pull model: the control node only makes outgoing HTTPS connections, no inbound access to the lab network.
Depends on v0.2.0 items 0 (shared groundwork) and 1 (inventory synchronization, host mapping).

**Zephyr**
- [ ] Catalog of job templates: only allowed operations (`win_workman` role/action, named playbooks), with a parameter schema and the allowed targets; published by the control node from its installed roles and playbooks
- [ ] Job model: scope, control node, linked `Task`, target inventories (`TaskInventory`), status (queued, claimed, running, succeeded, failed, cancelled, expired), requested/approved by, execution window, expiry
- [ ] Map the inventories to the Ansible hosts (`Inventory` has `mac_address` but no hostname/inventory name)
- [ ] Runner API: atomic claim with a lease (`GET /api/runner/jobs/next`), heartbeat, log chunks, report (`POST /api/runner/jobs/{id}/report`: exit code, PLAY RECAP per host, log)
- [ ] Apply the report to the task: per-host outcome on `TaskInventory` (`completed_at`, `has_anomalies`, `note`), task status
- [ ] Panel UI: job templates, job queue, approval, report view

**Control node (`lineadicomando.zephyr` collection)**
- [ ] systemd service that polls, runs and reports; reuse the ansible-win_edulab runner (`runlog.py`: streamed logs, `.done` sentinel, secret redaction, preview)
- [ ] Structured per-host results through a callback plugin or `ansible-runner` events, instead of parsing the log

**Security**
- [ ] Whitelist enforced on the control node too: no free commands (`run_powershell`), no arbitrary extra-vars, no targets outside the allowed ones
- [ ] Secrets stay on the control node (Vault); redact the report before sending it
- [ ] Job expiry and execution windows (e.g. a `restart` queued overnight must not run during lessons)
- [ ] Human approval, with preview (`preview`/`--check`), for destructive jobs (shutdown, restart, uninstall, group targets); required for jobs queued by the MCP agent
- [ ] One token per control node, with `runner:*` abilities, bound to one scope, revocable; heartbeat to detect offline nodes
- [ ] One job at a time per host; audit of request, approval, execution and outcome
- [ ] Pest tests: claim concurrency, expiry, approval, scope isolation, rejected templates

---

## Notes
- Current version: v0.1.2
- Target: v0.2.0 (integrations: Ansible API and MCP), then v0.3.0 (PWA)
- Additional items will be added later
