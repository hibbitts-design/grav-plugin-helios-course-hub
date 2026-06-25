# v1.2.3
## 06/25/2026

1. [](#bugfix)
   * Ensure og:image URL is absolute for social media preview cards

# v1.2.2
## 06/24/2026

1. [](#new)
   * Add single flat course root mode: plugin support and updated demo pages

# v1.2.1
## 06/22/2026

1. [](#improved)
   * Add custom URL option for header git icon link, independent of Helios GitHub Integration

# v1.2.0
## 06/22/2026

1. [](#bugfix)
   * Fix ?embedded=true not suppressing header/footer on default-toc and course-list pages — add chromeless support to base-simple-wide.html.twig and override base-simple.html.twig
   * Fix missing Open Graph meta tags on default-toc and course-list pages

1. [](#improved)
   * Update module blueprint title to "Course Module" for cross-plugin consistency
   * Add `languages/en.yaml` with THEME_HELIOS fallback strings for sites running without the Helios theme installed
   * Add i18n support for accessibility strings (Skip to content, Toggle/Close/Main navigation, Toggle submenu, theme toggle, search, View on GitHub/Codeberg) across all plugin-owned templates

# v1.1.8
## 06/20/2026

1. [](#improved)
   * Rename chapter→module and doc→course-page templates with backwards compatibility aliases; update demo pages and READMEs

# v1.1.7
## 06/20/2026

1. [](#improved)
   * Add course template (renamed from course-card) with backwards-compatible alias for existing installs. Upgrade note: rename each course card page's root file from `course-card.md` to `course-page.md` — the site renders correctly without this, but course card fields will no longer be editable in the Admin panel until the rename is done

# v1.1.6
## 06/17/2026

1. [](#improved)
   * Add Admin1/Admin2 theme warning notices with installed-but-inactive distinction

# v1.1.5
## 06/16/2026

1. [](#improved)
   * Add has() guard to shared shortcodes to prevent duplicate handler error when both plugins are active simultaneously

# v1.1.4
## 06/15/2026

1. [](#bugfix)
   * Update plugin templates and PHP to use Helios v2.1.6 renamed Twig variables (helios_* → doc_*, nav_tree)
   * Fix duplicate shortcode handler error on Grav v2.0 by guarding onShortcodeHandlers() against multiple firings

# v1.1.3
## 05/29/2026

1. [](#bugfix)
   * Fix sidebar image not found when filename has Admin-added numeric prefix

# v1.1.2
## 05/21/2026

1. [](#improved)
   * Updates for Helios theme v2.1.3: migrate base.html.twig to swap-body architecture and add missing TOC layout variables

1. [](#bugfix)
   * Fix redundant alt text on course card images

# v1.1.1
## 05/18/2026

1. [](#improved)
   * Add aria-hidden to footer icon and sr-only "opens in new tab" label
   * Trim trailing slash from repo value in footer git link URL
   * Removed no longer needed Admin2 font sizing

# v1.1.0
## 05/12/2026

1. [](#improved)
   * Add course-list card grouping and fix chapter grouping style/structure

# v1.0.9
## 05/11/2026

1. [](#improved)
   * Add admin label alignment adjustment for Admin 2 with optional config key
   * Reduce Admin2 zoom and prevent button text wrapping in large font size modes
   * Add chapter page child grouping support via group frontmatter field

# v1.0.8
## 05/04/2026

1. [](#improved)
   * Add optional title parameter to embed shortcodes for screen reader accessibility
   * Updated README
   * Backport "Pages" group header fix and relevance-based group sort from Open Reader

1. [](#bugfix)
   * Add x-cloak to the toggle wrapper so it stays hidden until Alpine finishes initializing
   * Fix sidebar aria-expanded attributes, sidebar image link close behaviour

# v1.0.7
## 04/25/2026

1. [](#improved)
   * Rescale admin font size options and update blueprint labels

# v1.0.6
## 04/25/2026

1. [](#improved)
   * Updated README
   
1. [](#bugfix)
   * Skip Helios plugin templates and assets when Helios theme is not active

# v1.0.5
## 04/20/2026

1. [](#improved)
   * Updated README

1. [](#bugfix)
   * Update with correct CSS files

# v1.0.4
## 04/19/2026

1. [](#new)
   * Added ?embedded=true and ?toc_position= URL params for LMS iframe embedding and per-request TOC control

1. [](#improved)
   * Updated plugin dependencies with version requirements

# v1.0.3
## 04/18/2026

1. [](#improved)
   * Updated README
   * Added Grav 2.0 / Admin 2.0 compatibility
   * Support changing font size in Admin 2
   * Update blueprints with Admin 1.7 specific info
   * Additionally support fallback theme Quark2

# v1.0.2
## 04/17/2026

1. [](#improved)
   * Updated README
   * Updated plugin dependencies

# v1.0.1
## 04/11/2026

1. [](#improved)
   * Add Page Inject example to cpt-363-3 demonstrating shared content between courses

# v1.0.0
## 04/09/2026

1. [](#new)
    * Added Helios-inspired Admin Styling toggle
1. [](#improved)
    * Include dark-mode screenshots
    * Updated README
    * Updated plugin description in blueprints.yaml
    * Browser tab title now uses "Page Title | Site Title" for single-course sites
    * Moved Helios colour preset to `helios-preset.yaml` for easier maintenance
    * Updated admin font size "default" option label to "Default (no changes)" for clarity
    * Filter search results by course published status and regroup by breadcrumb with course name prefix
    * Remove ReadMe from Search results
    * Decode HTML entities in breadcrumbs for correct display in search results
    * Add optional course sidebar image banner to course-card template
    * Rename 70.ux-techniques-guide to 70.guide in demo pages and update all README references
    * Add support for 3 cards per row in course list (blueprint, template, CSS, and README updates)
    * Auto-switch card image layout to top at 3 cards per row, with blueprint label and README updates

# v0.9.2
## 03/17/2026

1. [](#bugfix)
    * Updated blueprints.yaml homepage, demo, docs and issues

# v0.9.1
## 03/17/2026

1. [](#bugfix)
    * Updated blueprints.yaml version

# v0.9.0
## 03/04/2026

1. [](#new)
    * ChangeLog started...