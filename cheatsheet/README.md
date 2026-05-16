# Cheatsheet

A comprehensive, interactive reference for the wp-claude-template workflow. Lives in your project's `.claude/cheatsheet/` folder. Includes an AI Prompt Builder (Gemini-powered) that turns rough English into properly-formatted prompts.

---

## How to open it

The cheatsheet uses PHP (for component includes + the AI proxy), so it has to be served by a web server — not opened as `file://`.

**On Laragon (Windows):** the cheatsheet is accessible at the URL of your local site, under your theme:

```
http://<your-project>.test/wp-content/themes/<your-theme>/.claude/cheatsheet/
```

Replace `<your-project>` and `<your-theme>` with your actual project/theme names. The trailing `/` matters — Apache serves `index.php` automatically.

**On Local by Flywheel (Mac):** same idea — open the live URL of your project + the path to `.claude/cheatsheet/`.

**On any other PHP-enabled environment:** point a virtual host at the theme directory, or `cd` into the cheatsheet folder and run:

```bash
php -S localhost:8080
```

Then open `http://localhost:8080/`.

---

## Set up the AI Prompt Builder (optional but recommended)

The chat-style prompt builder uses Google's Gemini API. **Free tier:** 15 requests/minute, ~1500/day — more than enough for prompt-polishing during a build.

### One-time setup

1. Get a free API key at https://aistudio.google.com/app/apikey (sign in with Google, click "Create API key", you may need to create a Google Cloud project first — the page walks you through it).

2. Open `cheatsheet/.env` in this folder. You'll see:

   ```
   GEMINI_API_KEY=
   GEMINI_MODEL=gemini-2.0-flash-exp
   ```

3. Paste your key after the `=`:

   ```
   GEMINI_API_KEY=AIzaSyD-yourkeyhere
   ```

4. Save the file. Reload the cheatsheet page.

5. Click the floating ✨ button (bottom-right corner) or the "Open AI Prompt Builder" button in the prompt-builder section. Type rough English, get a polished prompt.

### Security

- `.env` is **gitignored** — your key never gets committed.
- The key is read by `api/polish.php` **server-side**. Your browser never sees it.
- All Gemini API calls happen via the local PHP proxy. No third-party scripts touch the key.

If you ever need to revoke the key: https://aistudio.google.com/app/apikey — find your key, delete it, generate a new one.

---

## File structure

```
cheatsheet/
├── index.php                ← main entry; includes all partials
├── .env.example             ← template (committed to git)
├── .env                     ← your real key (gitignored)
├── .htaccess                ← serves index.php, denies .env URL access
├── .gitignore               ← blocks .env from git
├── README.md                ← this file
│
├── api/
│   └── polish.php           ← Gemini proxy (reads .env, calls Gemini, returns to browser)
│
├── partials/                ← each section is its own component
│   ├── head.php
│   ├── header.php           ← top bar with Cowork/Code tabs + search
│   ├── sidebar.php          ← TOC
│   ├── hero.php
│   ├── quickstart.php
│   ├── install.php
│   ├── workflow.php         ← 6-step workflow
│   ├── prompt-builder.php   ← AI builder intro + Open button
│   ├── chat-popup.php       ← floating chat UI (always loaded)
│   ├── commands.php         ← all slash commands
│   ├── acf.php
│   ├── images.php
│   ├── tailwind.php
│   ├── filetree.php
│   ├── faq.php              ← 15+ Q&A items
│   ├── troubleshooting.php
│   ├── call-dev.php
│   ├── retro.php
│   └── footer.php
│
└── assets/
    ├── styles.css           ← all styles (design tokens + components + animations)
    └── app.js               ← tabs + search + scroll-spy + FAQ + chat + GSAP
```

---

## How to extend it

**Want to add a new section?** Create a new file at `partials/<name>.php`, add a `<section id="<name>" data-searchable>...</section>` block, then include it in `index.php` with `<?php wpct_partial('<name>'); ?>`. Add a matching sidebar link in `partials/sidebar.php`.

**Want to change brand colors?** Edit the `:root { --color-... }` block at the top of `assets/styles.css`. The cyan / violet tab accents change with one variable each.

**Want to change the AI prompt-polishing behavior?** Edit `api/polish.php` — the `$system_prompt` variable is what tells Gemini how to behave. The current prompt instructs Gemini to read `CLAUDE.md` for project rules, output one-line polished prompts, infer slugs from intent.

**Want to add chat history beyond a single turn?** The chat in `assets/app.js` already maintains a `chatHistory` array and passes it to the proxy. The PHP side accepts a `history` array and includes it in the Gemini call. So multi-turn refinement just works — click the "Refine" button on any AI response.

---

## Troubleshooting

**Page is blank / styles broken**
You're opening it as `file://`. PHP needs a server — use the Laragon URL above.

**"No Gemini API key configured" error**
Open `cheatsheet/.env`, paste your key, save, reload.

**"Network error reaching Gemini" or HTTP 502**
Either: (1) your local PHP doesn't have cURL enabled — extremely rare, ask your dev. (2) Google's API is rate-limiting you — wait a minute. (3) Your machine has no internet — check your connection.

**Chat opens but pressing Polish does nothing**
Open browser DevTools (F12) → Console tab. The exact error will be there.

**Cheatsheet is at `http://<project>.test/...` but Apache returns 404**
The `.claude/` folder starts with a dot. Apache may be configured to block dotfile directories. Either:
- Edit your Apache config to allow `.claude/`, OR
- Move the cheatsheet to a non-dotfile location (e.g. `theme/cheatsheet/`) and update the URL.
