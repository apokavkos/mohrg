# Project Brief

## Goal
Use this repository as a shared project brain for multiple projects, so AI sessions and collaborators can continue work quickly without relying on prior chat history.

## Why This Exists
- Preserve continuity when session limits or rate limits interrupt progress.
- Keep intent, links, prompts, and handoff notes in one versioned place across unrelated projects.
- Make onboarding fast for any new AI or human contributor.

## What Good Looks Like
- A new contributor can understand project status in under 5 minutes.
- Handoffs are clear, current, and actionable.
- Session logs capture decisions, blockers, and next steps.
- Multiple projects can coexist without mixing context.

## Repository Usage Model
- `fresh project template/` contains reusable starter context files.
- Each project gets its own folder created from that template.
- `eve/` is one example project folder, not the only project.

## Project Folder Pattern
For each project, keep this structure:
- `PROJECT_NAME/HANDOFF.md`
- `PROJECT_NAME/CONTEXT.md`
- `PROJECT_NAME/URLS.md`
- `PROJECT_NAME/PROMPTS/`
- `PROJECT_NAME/SESSIONS/`

## Operating Rules
1. Select the target project folder first (for example, `eve/`).
2. Start by reading that project's `HANDOFF.md`, `CONTEXT.md`, and `URLS.md`.
3. Record each work session in that project's `SESSIONS/`.
4. Save reusable prompts in that project's `PROMPTS/`.
5. Update handoff notes before ending a session.

## Brief Change Policy
- Treat this file as stable by default.
- Do not modify this Project Brief unless I explicitly request it.
- An exception is allowed only when the assistant has prompted multiple times that updating this brief is a strong recommendation.
