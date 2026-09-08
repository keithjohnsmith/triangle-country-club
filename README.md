# Triangle Country Club — Concept B ("Bushveld Basecamp")

A second, standalone design concept to show alongside the main (Next.js) site —
a **completely different feel** so Triangle can compare two directions.

## The difference at a glance
| | Concept A (main) | **Concept B (this)** |
| --- | --- | --- |
| Stack | Next.js 14 + React | **Static HTML / CSS / vanilla JS** |
| Feel | Quiet navy-serif luxury | **Bold, warm, modern adventure** |
| Type | Cormorant Garamond + Inter | **Space Grotesk + DM Sans** |
| Palette | Midnight navy + brass | **Terracotta + amber + espresso on cream** |
| Shape | Sharp editorial | Rounded, generous, pill nav |
| Structure | Multi-page | **Single-page scroll story** |
| Hook | Heritage/prestige | **"Basecamp for Gonarezhou"** |

Same corrected content as the main site: founded **1960**, **Murray MacDougall**,
**45 chalets**, **Baobab Restaurant**, sports = golf/cricket/tennis/squash/lawn
bowls, **Gonarezhou** stop-over positioning.

## Run it locally
It's a static site — no build step. Any static server:
```bash
cd triangle-country-club-concept
python -m http.server 5090
# open http://localhost:5090
```

## Deploy (cPanel)
Zip the **contents** of this folder and extract into the target web root
(`public_html` or a staging subdomain). No Node runtime needed. The only external
dependency is Google Fonts (loaded via `<link>`); everything else is local.

## Assets
- Images in `images/` are the optimised WebPs shared with Concept A — **placeholders
  until the client's new curated photos arrive** (a full RAW shoot sits in the main
  project's `Triangle/` folder).
- Hero uses the existing drone clip (`video/hero.*`); starts via JS after load.
