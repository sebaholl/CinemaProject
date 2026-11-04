# CinemaProject — Semester Exam Project (Web Programming + Backend & Databases 1)

## What this is
A mini CMS / dynamic web page (DWP) for a cinema, built for a two-course semester
exam (Web Programming, and Backend & Databases 1). Graded on course learning goals
plus the complexity, completeness and quality of the system, the database, and a
written report (max 10 pages, split evenly between programming and database).
Design/aesthetics are not graded but nice to have. . 

there are 2 deadlines: 8th november is the 80% mark the project should be done 80% and the 16th of december it is the actual deadline

## Tech stack (fixed by the assignment — do not introduce alternatives)
- **Vanilla PHP only** — no frameworks (no Laravel, Symfony, Slim, etc.)
- **MySQL/MariaDB**, managed via phpMyAdmin
- HTML/CSS/JS
- **Three.js** for the 3D seat picker (uses a free `.glb` chair model)
- VS Code, GitHub, local dev via XAMPP
- Must ultimately run on a **live server** (not just localhost) — the finished
  site has to actually be hosted and reachable.

## Current status
- The interactive 3D seat picker (Three.js) exists as a **standalone frontend
  prototype** — not yet wired into this repo/backend.
- No backend, database, login, or booking logic exists yet.
- Repo is currently just a placeholder README — everything else is still to build.

## Required features

### Frontend
- Contact form that emails the owner
- Movie and venue lists, news section — all DB-driven
- Front page: news, daily showings, company presentation — all DB-driven
- User profile page (PHP-handled login/registration)
- Mock checkout (no real payment) that creates a booking + invoice record
- Booking page using the 3D seat picker, wired to the database
- Seat reservation rules enforced server-side, e.g.:
  - max 5 seats per customer
  - max one empty seat between separate reservations (no awkward single gaps)
  - already-booked seats must render as unavailable in the 3D view

### Admin backend (session-protected)
- Booking overview + invoice creator
- CRUD for: company description, opening hours, contact info, movies/showings,
  front-page daily showings, news

### Database
- Relational DB; model with an ER diagram, convert to a relational model
- Normalized to 3NF
- At least **2 views**, **2 triggers**, **2 indexes** — all actually used by the
  DWP (e.g. view: showings with seats remaining; trigger: prevent double-booking
  or auto-update seat availability; index: showing date, seat+showing composite)
- A **hand-written** SQL script (not a phpMyAdmin export) that creates the whole
  schema including test data, views, and triggers — commit this to GitHub

### PHP / OOP
- At least **3 classes** that are actually instantiated in the running system
  (e.g. `Database`, `User`, `Booking`, `Movie`, `Showing`)
- Implement (or at least describe) **one design pattern** — e.g. Singleton for
  the DB connection, or Repository
- Separate presentation from logic; handle sessions and security properly
  (prepared statements, password hashing, session-based auth for admin, input
  validation/escaping on output)

## Report (write after the build is functionally done)
Max 10 pages, split evenly between the two halves.

**Web Programming half** must cover: site map (page-by-page, user-facing
description), architecture (presentation vs. logic separation), a flowchart
(e.g. the booking flow), technical deep-dive on a couple of hard parts (e.g.
seat picker ↔ PHP/DB integration), session handling, security, and how/why OOP
and the chosen design pattern were used.

**Database half** must cover: entity/relation/data-type choices and rationale,
ER diagram + relational model, advanced SQL query examples, PHP-to-DB code
snippets, and reflections on security, performance, and workflow.

## Working conventions for this project
- Keep presentation (HTML/templates) and logic (PHP classes/business rules)
  separated — the report explicitly has to describe this separation, so the
  codebase should make it easy to point at.
- Prefer prepared statements (PDO or mysqli) everywhere — no raw string-built
  SQL with user input.
- Keep the hand-written SQL schema script in the repo and keep it the source of
  truth — don't let phpMyAdmin-exported SQL replace it.
- Since the seat reservation rules (max 5 seats, max one gap seat) are graded
  requirements, enforce them server-side, not just in the 3D frontend.

## Role and working style
This is a 2-person team project. Claude acts as coding partner and planner —
writes the code, but both teammates need to understand it well enough to
explain and defend it at the oral exam.

- **Planning questions**: when asked a question, answer with the overall
  project flow and long-term file/architecture layout in mind, not in
  isolation — then present the answer clearly. Treat prior answers in this
  project as settled context for later questions so follow-ups don't
  contradict earlier guidance (save durable decisions to memory, not just
  conversation).
- **Code**: write complete, working code, not fragments — and always say
  which file it goes in.
- **Build order** (check in with the user before moving to the next step):
  1. ER diagram + SQL script
  2. PHP classes and DB connection
  3. Seat picker wired to the database
  4. Login / profile
  5. Mock checkout and invoice
  6. Admin dashboard
  7. News / contact / front page
- **Explanations**: short — a few lines on *why* a key choice was made, so
  it can be defended orally. No long lectures. Plain English.
- **Comments in code**: simple English, so the team can explain the code.

### Code rules
- Vanilla PHP only. No frameworks or Composer packages unless approved first.
- OOP with at least 3 real instantiated classes; logic kept separate from HTML.
- Always PDO prepared statements. `password_hash()` for passwords. Validate
  input, escape output, check the session on every admin page. CSRF protection
  on forms.
- The Three.js seat picker stays as-is — connect it via a PHP JSON endpoint
  for seat availability, and post selected seats back to PHP. Reservation
  rules are enforced **server-side**, never just in JavaScript.
- Never do anything that breaks an assignment requirement — flag it if a
  request would.

### Database rules
- Schema stays in 3NF; briefly justify normalization decisions.
- Hand-write SQL in this order: tables → indexes → views → triggers → test data.
- For every view, trigger, and index, note where in the DWP it's actually used
  (the assignment requires this traceability).

### Mock checkout
- Fake card form with a success/fail result. Never store real card data.
  Only create the booking + invoice on "success".

### Report help
- After each finished feature, give a short note on what to add to the report
  (which section, which snippet/diagram to include).
- Keep the report evenly split between programming and database sections.
- When editing the team's own writing, preserve their voice — don't make it
  overly formal.

### When asked for help
- Debugging: ask for the error message and relevant code first.
- Planning: check the request against the assignment requirements and flag
  any gaps.
