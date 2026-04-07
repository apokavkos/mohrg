# mohrg

A shared project brain for multiple AI-assisted projects. Each project gets its own folder that acts as a persistent, versioned knowledge base — so any AI agent or human contributor can pick up work quickly without relying on prior chat history.

## How It Works

- **`PROJECT_BRIEF.md`** — Defines the operating model and rules for this repository. Read it first.
- **`fresh project template/`** — Reusable starter folder for new projects. Copy it and rename the folder to start a new project.
- **`eve/`** — Active project: SeAT Asset Manager, an EVE Online industry plugin for SeAT 5.x.

## Starting a New Project

1. Copy `fresh project template/` into a new folder named after your project.
2. Fill in `CONTEXT.md` with the project's purpose and tech stack.
3. Add reference links to `URLS.md`.
4. Log each working session in `SESSIONS/` using the naming format `YYYY-MM-DD-HHMM-agent-name.md`.
5. Save reusable prompts in `PROMPTS/`.
6. Update `HANDOFF.md` before ending every session.

## Starting a New Session on an Existing Project

1. Open the project folder (e.g., `eve/`).
2. Read `HANDOFF.md`, `CONTEXT.md`, and `URLS.md` in that order.
3. Do the work, then log it in `SESSIONS/` and update `HANDOFF.md`.

## Repository Rules

See `PROJECT_BRIEF.md` for the full operating model, agent responsibilities, and change policy.
