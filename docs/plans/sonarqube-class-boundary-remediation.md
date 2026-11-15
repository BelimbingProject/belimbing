# SonarQube class-boundary remediation

**Status:** In progress — immediate complexity work is merged; Chat attachment presentation is extracted and awaiting validation.

**Last Updated:** 2026-11-15

**Sources:** SonarQube Cloud project `BelimbingApp_lara` on `main`; `app/Base/Schedule/Livewire/Index.php`; `app/Base/Schedule/Services/ScheduleRunRecorder.php`; `app/Base/Software/Services/SoftwareSourceRepository.php`; `app/Base/Software/Livewire/Deployment/Index.php`; `app/Base/Support/Git/GitRepository.php`; `app/Base/Foundation/ModuleManifest/ModuleManifestReader.php`; `app/Base/Settings/Services/DatabaseSettingsService.php`; `app/Core/AI/Livewire/Chat.php`; `app/Core/AI/Services/ControlPlane/LifecycleControlService.php`; `app/Core/Company/Livewire/Companies/Show.php`; `app/Core/User/Livewire/Users/Show.php`; `app/Core/User/Models/User.php`.

**Agents:** {root/GPT-5}

## Problem Essence

SonarQube reports twelve `php:S1448` findings because several Base and Core classes exceed the configured twenty-method boundary. Treating this as a line-count exercise would create shallow helpers and move framework glue into less coherent locations, increasing rather than reducing architectural entropy.

The 2026-11-15 SonarQube Cloud check authenticated successfully and reports a green quality gate, no reviewable security hotspots, and zero new duplicated lines. The remaining findings are therefore maintainability work, not a release-quality-gate failure.

## Desired Outcome

Each remaining finding has a clear owning boundary with a focused public surface: UI components retain interaction orchestration, models retain intrinsic relationships and state, and services retain one cohesive capability. The resulting classes should fall below the rule threshold without changing observable behavior.

## Top-Level Components

- Schedule separates override persistence from Livewire interaction and run recording from query/reporting responsibilities.
- Base infrastructure separates source-repository, Git, module-manifest, settings, and deployment-view concerns along their existing named operations.
- Core AI separates chat interaction and control-plane lifecycle responsibilities into cohesive collaborators.
- Core Company and User screens share presentation concerns only where the behavior is genuinely the same; the User model keeps only intrinsic model behavior.

## Design Decisions

Keep the existing classes and suppress `S1448` where the framework shape is large; this has the smallest code change but leaves the genuine mixed responsibilities and accumulating maintenance cost in place.

Extract arbitrary private methods until every class is below twenty methods; this satisfies the metric but creates shallow modules, hides ownership, and does not improve a caller's interface.

Recommended: stage behavior-preserving extractions by named responsibility, starting with the most cohesive service and Livewire seams. Prefer an existing concern or a focused collaborator with a small contract; use a trait only when sibling classes truly share the same semantics. This satisfies the root architectural rules on deep modules and low entropy without creating abstractions merely to satisfy Sonar.

## Public Contract

Routes, Livewire public properties/actions, persisted values, model relations, and service entry points retain their current behavior. New collaborators remain internal to their owning Base or Core module unless a caller outside that ownership boundary proves a stable shared contract is needed.

## Phases

### Completed immediate complexity remediation

- [x] Extract atomic schedule override persistence from `app/Base/Schedule/Livewire/Index.php` into `ScheduleOverrideWriter` without changing the Livewire public interaction contract. {root/GPT-5}
- [x] Prove the schedule override path with `tests/Feature/Schedule/ScheduleCronOverrideTest.php` and Pint; merge as PR #1019. {root/GPT-5}

### Schedule and Base infrastructure

- [ ] Partition `ScheduleRunRecorder` into recorder-facing orchestration and a nameable query/reporting collaborator only if the resulting public responsibilities are independently coherent.
- [ ] Split `SoftwareSourceRepository`, `GitRepository`, `ModuleManifestReader`, and `DatabaseSettingsService` one at a time, retaining their current domain exception boundaries and keeping growing-table access bounded.
- [ ] Reduce `Deployment\\Index` only through an existing or focused Livewire concern when three or more related interaction methods move together.
- [ ] Validate each extraction with its nearest Feature or Unit tests, Pint, and a review of its public entry points.

### Core interaction and model boundaries

- [x] Extract Chat attachment presentation from `Core/AI/Livewire/Chat` into `FormatsChatAttachments`, preserving its private mapping and attachment-route contract. {root/GPT-5}
- [ ] Separate `LifecycleControlService` at named lifecycle seams while preserving all provider/control-plane behavior.
- [ ] Extract cohesive presentation support from the Company and User Show components only when it avoids coupling their distinct authorization and state behavior.
- [ ] Move non-intrinsic User behavior to a focused collaborator or concern, leaving relations, casts, and identity behavior visibly owned by the model.
- [ ] Validate targeted AI, Company, and User tests before running the broader suite; record any environment-specific test limitation separately from application regressions.

### Completion verification

- [ ] Re-query SonarQube Cloud after each merged batch and confirm which `php:S1448` findings were removed rather than assuming source method counts match analysis results.
- [ ] Run `php artisan test --stop-on-failure`, `vendor/bin/pint --dirty`, and the relevant asset build when browser resources change.
- [ ] Update this plan with merged PRs, remaining findings, and evidence; do not suppress the rule merely to close a metric.
