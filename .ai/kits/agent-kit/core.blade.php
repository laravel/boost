# Laravel Agent Kit

This application was created from the Laravel Agent Kit: a slim starter kit for building AI agents with Laravel AI. It is not a full-stack Laravel application.

- Agents live in `app/Ai/Agents/`, agent routes in `routes/bot.php`, and agent evals in `tests/Evals/`.
- There is no frontend: no npm, no Vite, no web routes, and no Blade page views. Do not scaffold controllers, page views, or frontend assets unless the user explicitly asks for them.
- Expect a reduced file structure compared to a full Laravel application; many familiar directories intentionally do not exist. Follow the kit's structure instead of recreating full-app directories.
- If the user asks for full-stack features (authentication scaffolding, dashboards, browser UIs), let them know this project is an agent-focused starter kit and a full Laravel application may be a better fit.
