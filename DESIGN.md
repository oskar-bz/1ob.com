# Design

Direction: **annotated** — a reading-first serif page, labelled like engineering documentation. The neo-industrial vocabulary (mono labels, square markers, corner brackets, box-drawing trees) lives in the chrome and never in the prose.

## Principles

1. Less is more. Every element earns its place; when in doubt, remove it.
2. Reading comes first: a column of about 70 characters, generous leading, no motion in body text.
3. Motion is interruptible (transitions, not keyframes, for anything interactive), short (< 400 ms), and the more often something is used, the shorter it is. Everything switches off under `prefers-reduced-motion`.
4. Nothing third-party at runtime. Fonts, maths, comments and analytics are all served from this domain.

## Tokens

| Token | Value | Use |
| --- | --- | --- |
| `--ground` | `#f4f2ec` | page |
| `--inset` | `#ebe8e0` | figures, code, maths, hover rows |
| `--rule` / `--rule-2` | `#dcd9d0` / `#cfccc2` | hairlines / tree lines, kbd |
| `--ink` | `#191917` | headings, strong rules, the layer under the footer |
| `--ink-2` / `--ink-3` | `#26251f` / `#46443e` | body / secondary text |
| `--muted` | `#64625b` | labels (5.6:1 on ground) |
| `--orange` | `#c13f12` | projects; active status |
| `--blue` | `#2f2fd0` | writing |

`--accent` is orange inside projects and blue inside posts: margin-note numbers, figure brackets, the current contents entry and the chip underline follow it.

**Type:** Literata for reading (and post titles), Geist for UI headings and project titles, Geist Mono for labels, dates, code and the logo. Labels are 11 px mono uppercase with 0.08 em tracking. `text-wrap: balance` on headings, `pretty` on paragraphs, tabular figures wherever numbers sit in a column.

**Layout:** container 1040 px with 32 px gutters (20 px on phones). Articles: main column 660 px, 56 px gutter, 240 px margin column for notes and captions. Below 1100 px notes fold into the text; below 1200 px the contents rail becomes a button; below 720 px the nav becomes a menu.

## Components and their motion

| Element | Behaviour |
| --- | --- |
| Logo | `1ob/` at rest. Hovering the header (not the logo, to avoid hit-box flicker) collapses `1ob`, slides the slash left and types `oskar` letter by letter. |
| Nav button (`.nl`) | A 9×7 square; on hover it grows to full height (120 ms), then wipes right behind the label (200 ms, 100 ms delay). Exit reverses: width first, then height. The current page stays filled. |
| Status | Filled orange = active · outline = planned · filled ink = shipped · half = paused · struck = abandoned. |
| Roadmap tree | CSS-drawn lines and boxes (never font glyphs). Done = crossed and struck through, now = filled orange square in a frame, todo = empty. |
| Brackets | Corner brackets on anything you can look at or play with. On the homepage lead they close in on hover. |
| Margin note | In the margin beside its line. On narrow screens the number is a tap target that unfolds the note inline (CSS only). |
| Contents | Dashes in the right margin, vertically centred; the current section's dash is accented and longer. Hover opens a panel (fade, 6 px slide, blur). Narrow: a 48 px button bottom right whose lines mirror the outline, opening a bottom sheet. |
| Copy button | The label swaps to "Copied" with opacity + blur + scale. |
| Mobile menu | Two bars turn into ✕; items rise in 40 ms apart. |
| Footer | Sign-off and directory on the page; the page is a sheet that lifts off an ink layer carrying `1ob/` at the full content width. Where supported, the wordmark rises slightly as it is uncovered (scroll-driven animation). |
| Entry | First page of a visit only (decided by referrer, nothing stored): the homepage headline rises word by word (700 ms, 32 ms apart), then the links and projects follow. |
| Page transitions | Cross-document View Transitions: 200 ms crossfade; the header stays still; a project's or post's title morphs from the list into the page heading. Browsers without support navigate normally. |
