# Scope Deletion Workflow Design

## Context
Il sistema usa `scopes` come boundary multi-tenant logico e applica filtering per `scope_id` su più tabelle dominio.
L'obiettivo è rendere sicura e governata la dismissione di uno scope, evitando cancellazioni immediate e impedendo operazioni distruttive sullo scope di default.

## Goals
- Impedire disattivazione o cancellazione dello scope default.
- Introdurre cancellazione differita (grace period) per gli scope dismessi.
- Eseguire purge reale tramite task schedulato Laravel.
- Cancellare record collegati con query dirette (senza eventi Eloquent).
- Rendere la soluzione riusabile per altri task periodici.

## Non-Goals
- Non introdurre soft delete su `scopes`.
- Non introdurre event sourcing o listener per il purge.
- Non rifattorizzare ora tutte le migration FK esistenti per `ON DELETE CASCADE`.

## Data Model Changes
Modificare la migration esistente `database/migrations/2014_10_12_050000_create_scopes_table.php` (come richiesto, senza nuova migration):
- `protected` boolean, default `false`.
- `pending_delete` timestamp nullable, indicizzato.

Seed del record default:
- `protected = true`.

Model `App\\Models\\Scope`:
- Aggiungere `protected` e `pending_delete` in `$fillable`.
- Aggiungere cast:
  - `protected` => boolean
  - `pending_delete` => datetime

## Configuration
Aggiungere in `.env.example`:
- `SCOPE_DELETE_GRACE_HOURS=24`

Aggiungere in `config/app.php` (o nuovo `config/scopes.php` se si preferisce isolamento):
- `scope_delete_grace_hours` con fallback a 24.

Decisione raccomandata:
- usare `config/scopes.php` per evitare accoppiamento semantico con config generale app.

## Business Rules
1. Scope protetto (`protected=true`):
- non disattivabile.
- non cancellabile.
- non schedulabile per cancellazione (`pending_delete` non impostabile).

2. Richiesta cancellazione scope:
- consentita solo se `is_active=false`.
- non elimina subito.
- imposta `pending_delete = now()->addHours(config('scopes.delete_grace_hours', 24))`.

3. Scope già in pending delete:
- una seconda richiesta non crea nuovo stato; può essere idempotente (lascia invariato) o aggiornare timestamp. Raccomandazione: idempotente (non estendere accidentalmente la retention).

4. Riattivazione scope:
- se consentita da UX, deve azzerare `pending_delete`.

## Deletion Execution (Cron Workflow)
### Tecnologia Laravel
Laravel fornisce scheduler nativo:
- definizione task in `routes/console.php`.
- esecuzione via sistema operativo con `php artisan schedule:run` ogni minuto.

### Componente applicativa
- Nuovo comando Artisan: `scopes:purge-pending`.
- Scheduling in `routes/console.php` con cadenza oraria (o ogni 15 minuti).
- Hardening scheduler:
  - `withoutOverlapping()`
  - `onOneServer()` se deploy multi-nodo
  - logging outcome (scope processati/falliti)

### Algoritmo purge
Per ogni scope con `pending_delete <= now()`:
1. transazione DB.
2. lock record scope (`for update`) per evitare race.
3. ricontrollo guardrail (`protected=false`, `is_active=false`, `pending_delete` scaduto).
4. delete diretto su tabelle collegate (`where scope_id = ?`).
5. delete relazioni pivot scope-user.
6. delete riga scope.
7. commit.

In caso errore su singolo scope:
- rollback scope corrente.
- continua sui successivi.
- traccia errore nei log.

## Connected Tables Strategy
Poiché si richiedono query dirette, serve una lista esplicita e mantenibile.

Approccio raccomandato:
- classe dedicata `ScopePurgeRegistry` che espone array ordinato di tabelle/pattern di cancellazione.
- il comando itera il registry per cancellazioni consistenti.

Nota ordine:
- eliminare prima eventuali tabelle figlie che dipendono da altre con `scope_id`.
- poi entità aggregate.
- poi pivot `scope_user`.
- infine `scopes`.

## UX / Backoffice Behavior
Nella gestione Scope (Filament resource):
- disabilitare toggle `is_active` quando `protected=true`.
- nascondere/negare azione delete quando `protected=true`.
- azione "Richiedi eliminazione" disponibile solo se `is_active=false` e `protected=false`.
- mostrare badge/colonna `pending_delete` quando valorizzata.
- opzionale: azione "Annulla eliminazione" (reset `pending_delete`) per rollback operativo.

## Security & Integrity
- Le policy autorizzano il "chi" può operare; i guardrail su `protected/is_active/pending_delete` devono stare anche nel dominio applicativo per evitare bypass via endpoint non UI.
- Il comando cron deve ripetere i guardrail prima del delete reale.

## Testing Strategy
### Feature tests
1. `default` protetto non disattivabile.
2. `default` protetto non eliminabile.
3. richiesta delete scope attivo => errore validazione.
4. richiesta delete scope inattivo => `pending_delete` impostato correttamente.
5. comando purge non elimina scope non scaduti.
6. comando purge elimina scope scaduti e dati collegati.
7. comando purge salta scope protetti anche se `pending_delete` impostato manualmente.

### Unit tests (opzionale)
- test su calcolo timestamp retention.
- test su registry tabelle purge.

## Operational Notes
- In produzione va configurato cron OS:
  - `* * * * * php /path/to/artisan schedule:run`
- Per task futuri, riusare pattern: comando dedicato + scheduler + guardrail + logging.

## Trade-offs
- Query dirette migliorano performance e prevedibilità, ma non eseguono eventi/model hooks.
- Lista tabelle esplicita richiede manutenzione quando si aggiungono nuove entità scoped.
- Modificare migration esistente è coerente con richiesta, ma richiede attenzione su ambienti già migrati (necessario allineamento separato se DB già in uso).

## Rollout Plan
1. aggiornamento schema e model.
2. implementazione guardrail delete/deactivate su flusso Scope management.
3. implementazione comando `scopes:purge-pending`.
4. aggancio scheduler in `routes/console.php`.
5. test automatici.
6. verifica manuale con scope demo in pending delete.

## Acceptance Criteria
- lo scope default non può essere disattivato né cancellato.
- uno scope attivo non può essere cancellato.
- richiesta cancellazione imposta `pending_delete` a `now + grace_hours`.
- allo scadere, il cron elimina scope e record collegati con query dirette.
- il pattern scheduler è riusabile per futuri task periodici.
