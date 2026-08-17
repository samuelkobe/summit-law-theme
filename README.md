# Summit Law LLP — WordPress Theme

Custom WordPress theme for [Summit Law LLP](https://summitlaw.ca), a mediation and dispute resolution law firm. Built with Vite, Tailwind CSS, and ACF custom blocks, with deep Amelia booking plugin integration for managing mediation sessions.

Developed by [Web Ok](https://webok.ca).

---

## Tech Stack

- **Build tool:** Vite 6
- **CSS:** Tailwind CSS 3 with PostCSS and autoprefixer
- **Blocks:** ACF Pro (20+ custom blocks)
- **Booking:** Amelia booking plugin (custom integration)
- **PHP:** Custom post types, taxonomies, roles, and REST endpoints

---

## Development

Install dependencies:
```bash
npm install
```

Start the dev server (with live reload for PHP files):
```bash
npm run dev
```

Build for production:
```bash
npm run build
```

Watch mode:
```bash
npm run watch
```

> **Note:** The `dist/` folder is excluded from version control. Run `npm run build` after cloning before activating the theme.

---

## Custom Post Types & Taxonomies

| Post Type | Description |
|---|---|
| `post` (Insights) | News and articles, rewritten to `/insights/` |
| `case` | Case studies |
| `service` | Legal services (hierarchical, parent/child) |
| `team` | Team member profiles |
| `mediation_intake` | Intake forms linked to Amelia bookings |

**Taxonomy:** `area` — single-select (radio button) taxonomy used across Insights, Cases, and Services for practice area filtering.

---

## Custom Blocks (ACF)

20 custom ACF blocks covering:
- Hero banners (home and interior)
- Content groups, bullet groups, and accordions
- Services loop, team loop, posts loop
- CTA, mini banner, content banner
- Triple cards, affiliations
- Form blocks, breadcrumbs

---

## Amelia Booking Integration

The theme has a custom mediation intake and case management system built on top of the Amelia booking plugin.

### Mediation Intake (`inc/mediation-intake.php`)

- A `mediation_intake` custom post type is created for every Amelia booking tagged as a mediation service
- Intake forms capture party information (plaintiffs, defendants, third parties), counsel details, and document status
- Booking IDs are linked via a short-lived transient on Amelia's `amelia_after_appointment_booking_saved` hook
- REST API endpoints allow external lookup by booking ID, gated by a server-generated secret key stored in `wp_options`
- Microsoft Azure OAuth integration for calendar/Zoom meeting data retrieval

### Document Upload Portal (`inc/mediation-upload.php`)

- Token-based upload portal accessible at `/mediation-upload/{32-char-token}/` — no login required
- Parties and counsel receive a unique token-gated URL to upload signed agreements and briefs
- Tokens expire at the session booking date
- Uploaded files are stored in a protected directory outside the web root
- Email notifications are sent to the legal assistant on every upload

### Reminder System (`inc/mediation-reminders.php`)

Automated email reminders are triggered via WP-Cron based on booking dates:

- Agreement request — sent shortly after booking
- Brief request — sent 2 weeks before session
- Session reminder — sent 7 days and 1 day before
- Discovery call reminder
- Internal team notifications include Zoom URL when available

### Team Booking (`parts/team-booking.php`)

Individual team member pages can embed an Amelia booking shortcode, controlled via an ACF toggle (`bookable_services_toggle`).

---

## Custom Roles & Capabilities

A **Manager** role is registered with editor-level capabilities plus:
- Access to the customizer and menu management
- Cannot switch themes or install plugins

The `mediation_intake` CPT uses explicit capability grants so only administrators, editors, and managers can access intake records.

---

## Search

Extended site search covers all custom post types (`case`, `team`, `service`, `post`). ACF fields are included in the search index (role, short description, email, phone, affiliations, education) with custom relevance scoring.

---

## Performance

- Critical CSS inlined in `<head>`
- Fonts preloaded (HankenGrotesk, PPMuseum)
- LCP hero image preloaded per template
- Async CSS loading with `<noscript>` fallback
- Vite-based module bundling with code splitting

---

## Admin Customizer Options

- Alternate logo (for light backgrounds)
- Footer address and Google Maps link
- Footer copyright / attribution text
