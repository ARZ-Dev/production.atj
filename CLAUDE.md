# production.atj

## Start of every session

**Read [CHAT-CONTEXT.md](CHAT-CONTEXT.md) before doing any work in this repo.** It is the consolidated context from all previous chat sessions and covers:

- The cross-service architecture (this app vs. the `auth-service` parent at `C:\wamp64\www\auth-service`, DB `erp_auth`) and the inventory API contract — **inventory and all item/unit/warehouse master data live in the parent, not here**
- Domain rules for plans, events, the event lifecycle, warehouse resolution, and stock documents
- Conventions and gotchas that are easy to get wrong (icon font, bootstrap-select, Cleave.js, Livewire, CSS specificity, tooling)
- A per-session log of what was built and why
- Open items and known issues

Much of it is not derivable from the code — it records decisions, rejected alternatives, and traps that already cost time once.

## Keeping it current

When a session makes a decision, establishes a convention, or resolves/adds an open item, update the relevant section of `CHAT-CONTEXT.md` as part of the work — don't let it drift.
