# 1ob.com

Personal site of Oskar Behzadpour. Static HTML built with [Astro](https://astro.build), served by Apache on all-inkl, plus three small PHP endpoints: Hacker News comments and the self-hosted analytics beacon and dashboard.

```
src/
  content/projects/*.md(x)   one file per project  (schema: src/content.config.ts)
  content/writing/*.md(x)    one file per post
  components/                Header, Footer, Toc, Sidenote, Figure, Comments, Share, Roadmap, Status
  layouts/                   Base (every page), Article (projects + posts)
  pages/                     routes
  styles/global.css          the whole design system, one file
  site.ts                    name, links, headline: everything personal
public/
  .htaccess                  redirects from the old site, caching, security headers
  api/hn.php                 Hacker News mirror (cached, sanitised)
  analytics/                 beacon.php, dashboard (index.php), MaxMind reader
  data/                      server-side state; denied to the web; never in git
```

## Working on it

```sh
npm install
npm run dev        # http://localhost:4321, fast reload; PHP endpoints are not available here
npm run preview    # production build served by PHP: http://127.0.0.1:8080, comments + analytics work
```

Requires Node 22 and, for `preview`, PHP ≥ 8.1 with `pdo_sqlite`.

## Writing content

A post is a Markdown or MDX file in `src/content/writing/`. Its file name is the URL: `foo.mdx` → `/writing/foo/`.

```mdx
---
title: A mel spectrogram without an FFT
dek: One or two sentences that state the finding.
date: 2026-09-02
tags: [Speech, Hardware]
project: kws-max10        # optional: links the post to a project
hn: "41234567"            # optional: Hacker News item id, once submitted
draft: true               # optional: visible in dev only
---

## A section heading      ← every h2/h3 appears in the table of contents

A sentence with a margin note.<Sidenote>The note. Maths works: $1/k^2$.</Sidenote> More text.

<Figure n="F1" caption="Caption in the margin." frame>
  ![alt text](./image.png)
</Figure>

$$ m = 2595 \log_{10}(1 + f/700) \tag{1} $$
```

Projects work the same way (`src/content/projects/`), with extra front matter: `order`, `summary`, `status` (`active` · `planned` · `shipped` · `paused` · `abandoned`), `why` (for paused or abandoned projects), `featured` (1 = homepage lead, 2–4 = the row below), `spec` and `roadmap`. See `kws-max10.mdx` for a complete example.

**Hacker News:** submit the post, put the item id in `hn:` and push. Comments then appear under the post (refreshed every five minutes, server-side). To hide a comment and its replies, add its id to `data/hn-hidden.json` on the server, for example `[41234567]`. Put your HN username into `AUTHOR` in `public/api/hn.php` to get the "Author" tag.

## Deploying

Push to `main`. The GitHub Action builds the site and uploads `dist/` over FTPS. Set these repository secrets once: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_DIR` (the domain's folder on the server). In KAS, set the PHP version to 8.1 or newer and enable "SSL erzwingen".

Without the Action: run `npm run build` and upload the contents of `dist/` with any FTP client. Never delete the server's `data/` folder.

### One-time server setup

1. Make `data/` writable for PHP (it usually is by default on all-inkl).
2. Analytics password: generate a hash locally with `php -r "echo password_hash('…', PASSWORD_DEFAULT);"` and create `data/config.php` on the server, following `public/analytics/config.example.php`. The dashboard is at `/analytics/`.
3. Optional: country statistics. Download `GeoLite2-Country.mmdb` from MaxMind (free account; its licence requires attribution) and upload it to `data/`.
4. Optional, as a second lock: protect `/analytics/` with KAS "Verzeichnisschutz".

## Before launch

- [ ] Real content; remove the sample thread (`hn: fixture` in `mel-without-fft.mdx`, `public/data/hn-fixture.json`)
- [ ] `src/site.ts`: email address, HN handle
- [ ] Impressum: address (§ 5 DDG). Datenschutz: the draft describes what this site actually does; have it checked
- [ ] Social preview image (`og:image`), if wanted
- [ ] The About page text

## Analytics, in short

Counts page views, visitors and sessions without cookies and without storing anything on the visitor's device. Visitors are a daily hash of IP + user agent + a random salt that is replaced every day, so hashes can't be linked across days or reversed. IP addresses are never stored. Country comes from a local GeoLite database; cities are not recorded. Do Not Track and Global Privacy Control are respected, bots and prefetches are dropped, 404s are logged with their referrer, and raw rows older than 400 days are deleted. Your own visits aren't counted while you're signed in to the dashboard.
