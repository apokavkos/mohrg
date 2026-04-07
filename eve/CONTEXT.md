# Project Context

## Project Name
- mohrg

## Purpose
- This repository is a shared project brain for multiple AI sessions.
- It stores context, references, prompts, and session logs.

## AI Operating Model
- GitHub Copilot: primary project manager and documentation assistant.
- Google CLI agent: primary execution agent for implementation work on the Linux host.
- Claude CLI: backup execution agent, with increased use expected for cross-session knowledge sharing.

## Agent Responsibilities
- Planning and coordination: GitHub Copilot.
- Task execution on server/host: Google CLI by default.
- Secondary execution and alternative solutions: Claude CLI.
- Documentation quality and handoff consistency: GitHub Copilot.

## Agent Handoff Rules
- Before execution, define objective, scope, and success criteria in HANDOFF.md.
- After each session, log actions and outcomes in SESSIONS/.
- If the primary execution agent stalls, re-run the task with the backup agent and compare results.
- Capture tool-specific gotchas and winning prompts in PROMPTS/ for reuse.

## Scope
- Keep docs current and lightweight.
- Record decisions and assumptions explicitly.
- Prefer append-only session logs in SESSIONS/.

## Working Agreements
- Read HANDOFF.md first at session start.
- Update HANDOFF.md before session end.
- Add or update reusable prompts in PROMPTS/.
- Keep URLS.md as the source of truth for references.
- Respect the AI Operating Model unless explicitly changed by the repo owner.

## Success Criteria
- Any new AI can continue work in under 5 minutes.
- Context is clear without chat history.
