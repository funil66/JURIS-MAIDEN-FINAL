# PR: Stabilize Dashboard & Widgets, Add Agenda, Tests & CI

## Summary
This PR completes the stabilization and UX work requested for the Dashboard and related widgets.

Highlights:
- Fixed Blade compiled-view ParseErrors by moving complex logic into widget helper methods.
- Completed a code-wide null-safety sweep for Filament `formatStateUsing`/closures.
- Added `Process` accessor and scope for `number` handling.
- Implemented the Agenda (calendar) page with a compact, responsive UI and a sidebar with upcoming events.
- Improved dashboard widgets for responsiveness and added subtle UI enhancements.
- Added unit tests for `CriticalAlertsWidget`, `SignatureStatsWidget`, and `AgendaPage` (DB-less where appropriate).
- Added a Smoke test checking essential views and named routes.
- Added GitHub Actions workflow to run PHPUnit (SQLite in-memory, file cache) to avoid external dependencies.
- Fixed Redis/CLI issues and resolved duplicate route slug collisions which prevented `route:cache`.

## Checklist
- [x] Null-safety sweep
- [x] Fix compiled Blade parse issues
- [x] Add Agenda page + view
- [x] Responsive UI improvements
- [x] Unit tests & smoke tests
- [x] CI workflow
- [x] Log monitoring script

## Notes
- A final monitoring run (1hr) is recommended in the staging environment to ensure no regressions.
- There are still a few lower-priority `formatStateUsing` occurrences that were reviewed and decided to keep (they're already null-safe), but we should watch logs.

## Next steps
- Merge to main, run staging deployment, and monitor logs for 1 hour.
- Add visual A/B tests and UI polish in the next iteration (optional).

---
