# Working on 1ob.com

Read `DESIGN.md` before changing anything visual and `README.md` for structure and deployment.

## Hard constraints

- Hosting is all-inkl shared hosting: static files + PHP (≥ 8.1, pdo_sqlite) + Apache `.htaccess`. No Node at runtime, no databases other than SQLite, no long-running processes.
- Nothing third-party at runtime: no CDNs, no external fonts, scripts, embeds or trackers. Readers' browsers only talk to 1ob.com.
- Nothing stored on the visitor's device (cookies, localStorage, sessionStorage) except the dashboard login cookie. This keeps the site free of consent banners under § 25 TDDDG.
- Server state lives in `data/` and must never be overwritten or deleted by a deploy.

## Style rules

- Less is more: solve problems with less code and fewer elements. Ask before adding sections or features.
- All styles live in `src/styles/global.css`, using its tokens. No new colours, fonts or font sizes without a reason in `DESIGN.md`.
- JavaScript only where CSS can't do it; every script must degrade gracefully without JS.
- Interactions use transitions (interruptible), not keyframes; keep them under 400 ms; respect `prefers-reduced-motion`.
- `text-wrap: balance` on headings, `pretty` on paragraphs, `tabular-nums` for columns of numbers.
- Accessibility: real buttons and links, 44 px touch targets, visible focus, 4.5:1 contrast for text.
- Check every change at 390 px and 1280 px wide (`npm run preview`, then screenshots).

## Writing on the site

Prose is plain and dense: no rhetorical flourishes, no hype, sub-clauses only where they carry logic. Every list entry gets a one-sentence dek that states a finding, not a topic.
