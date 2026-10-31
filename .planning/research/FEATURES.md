# Feature Landscape

**Domain:** Educational public-health information portal (COVID-19)
**Project:** Covid-flow Flask/SQLite rebuild
**Researched:** 2026-10-31
**Evidence confidence:** MEDIUM — primary WHO, CDC, OWASP, Selenium, and pytest documentation was found through a verified web-search path; the configured classifier assigns that path MEDIUM confidence.

## Product Boundary

Covid-flow is an informational, educational site, not a care-delivery system. It should make a visitor understand what the figures mean, where they came from, when they were observed or refreshed, and when guidance should lead them to an official or urgent-care resource. It must never imply that a chart is live when it is seeded or fallback data, or that guidance is a personalised diagnosis.

## Table Stakes

Features users and the academic assessment expect. Missing any of these makes the stated product scope incomplete.

| Feature | Why expected | Complexity | MVP acceptance notes |
|---|---|---:|---|
| Public home and dashboard | The core value is trustworthy, scannable COVID-19 information. | Med | Render headline statistics and one small trend chart; show geographic scope, source URL/name, data period, fetched/updated time, and an explicit `live`, `fallback`, or `unavailable` status. Empty/error states must be visible rather than silently substituting figures. |
| Statistics detail and provenance | WHO cautions that counts change retrospectively and are affected by detection, definitions, testing, and reporting. A number without provenance is misleading. | Med | Provide a short methodology/limitations block and a link to the authoritative source. Label locally seeded data as sample/fallback data. Do not call weekly data real-time. |
| Plain-language health guidance | Visitors need actionable prevention, illness, risk, testing/treatment, and emergency-information entry points. | Med | Use short, dated, source-linked sections: prevention, if sick, higher-risk groups, testing/treatment prompt, and emergency-warning-sign prompt. Include a prominent informational-only disclaimer and links to local/official services. No symptom scoring or treatment recommendation engine. |
| Responsive, accessible public UI | Public-health guidance must be usable on a phone and understandable beyond technical users. CDC health-literacy guidance supports plain language. | Med | Semantic headings, labelled controls, keyboard focus, sufficient contrast, alt text, responsive layout, chart table/summary fallback, and readable loading/error copy. |
| Account registration, login, logout | Required for profile, discussion ownership, and programme enrolment. | Med | Server-side validation, CSRF protection, password hashing, generic failed-login response, basic per-account throttling, and session logout. A student can demonstrate each outcome. |
| Profile | The brief explicitly calls for a profile and users need a minimal place to manage their account. | Low | Display editable non-sensitive profile fields and allow a password change only after current-password confirmation. The profile must show the user's own registrations/comments without exposing other users' data. |
| Community comments | The community element creates a simple authenticated interaction to test and demonstrate. | Med | Publicly list approved comments; signed-in users create comments; owner can edit/delete only their own comment. Store timestamps and a moderation state. Escape all content and enforce length/empty validation. |
| Programme catalogue and registration | Programme registration is a named learning objective and a complete form workflow. | Med | Seed a small programme list/detail page. Signed-in users register once per programme, receive a clear success/error state, and see/cancel their own registration. Do not collect health history or payment details. |
| Focused Quality Lab | Testing evidence and viva readiness are explicit deliverables, not an afterthought. | Med | A public page explains the test pyramid, representative journeys, how to run them, and report locations. It may display committed evidence only when it identifies run date, environment, command, and status; otherwise say results are unavailable/not executed. |
| Automated test journeys | The assessment requires unit, integration, and Selenium 4 coverage with reports and screenshots. | Med | Cover public dashboard status, guidance, valid/invalid auth, profile ownership, comment ownership/moderation, and programme registration. Selenium page objects model pages/components; assertions stay in tests; use explicit waits only. |

## Differentiators / Optional Scope

These additions are valuable because they make the portal unusually honest and easy to defend in a viva. They should follow the table-stakes flows, not delay them.

| Feature | Value proposition | Complexity | Notes |
|---|---|---:|---|
| Data-status and provenance panel | Makes source, scope, freshness, retrieval result, and fallback reason visible beside every statistic. | Low | Strongly recommended differentiator. It is more valuable than a larger chart catalogue because it prevents false claims about surveillance data. |
| “What changed?” data note | Explains that a source refresh/revision changed the displayed data, rather than pretending the old and new values are directly comparable. | Med | Keep an audit note in SQLite or a simple source-metadata record; do not build a full data warehouse. |
| Guidance source/date badges | Lets users see when guidance was reviewed and follow the authoritative material. | Low | Treat guidance as curated content with source URL, publisher, reviewed date, and scope. |
| Moderation queue for an administrator | Gives a safe, demonstrable control over community content. | Med | Minimal role flag and approve/hide action; seed one admin. It is optional if schedule is tight, but public comments should at least support a `pending/approved` state. |
| Test traceability and viva cards | Relates each important user flow to a unit/integration/Selenium test and explains why it exists. | Low | Static Quality Lab content plus links to real report/screenshot artifacts. Never invent pass rates. |
| Downloadable citation/limitations note | Helps a learner cite sources and understand data caveats. | Low | A simple print-friendly source page is enough; defer CSV export. |

## Anti-Features

Explicit exclusions protect public-health safety and keep the Flask/SQLite mini-project explainable.

| Anti-Feature | Why avoid | What to do instead |
|---|---|---|
| Diagnosis, symptom checker, risk score, or personalised treatment advice | Exceeds informational scope and can cause medical harm. | Provide general, dated guidance, warning-sign language, and links to official/urgent care resources. |
| Patient records, appointments, prescriptions, or health-history collection | Violates stated scope and creates privacy/regulatory risk. | Collect only minimum account and programme-registration fields. |
| Pretended live statistics or hidden fallback substitution | Conflicts with the project’s data-integrity requirement and WHO’s stated caveats. | Retrieve server-side when available; otherwise visibly label source/date/status and local fallback/sample data. |
| User-posted medical advice, replies, direct messages, or unmoderated forum | Multiplies misinformation, moderation, and safeguarding burden. | Limit community to short, moderated, non-clinical comments; publish conduct rules and report/hide workflow only if time permits. |
| News scraping, social feeds, notification delivery, payment, or complex registration logistics | Adds unstable integrations and operational work outside the academic learning goal. | Use seeded programmes and in-app confirmation only. |
| Password-reset email, MFA, social login, SSO, or account recovery workflow | Worthwhile at production scale but requires delivery infrastructure and more security-sensitive edge cases. | Deliver robust local registration/login/logout, hashed passwords, CSRF, generic errors, throttling, and current-password verification for password changes. |
| Rich analytics, arbitrary chart builder, maps, export pipeline, or multi-country comparison suite | Encourages interpretation beyond the quality/coverage of a small student data source. | One understandable dashboard, one trend chart, and provenance/limitations. |
| “Run tests” action in the public web UI | Executing browser tests from a public request is unsafe and obscures provenance. | Document developer commands and surface only pre-generated, accurately labelled evidence. |

## Feature Dependencies

```text
Public layout + accessibility primitives
  -> dashboard shell -> source adapter/seed fallback -> statistics cards/chart/status panel
  -> guidance content -> source/date badges + informational disclaimer

SQLite schema + CSRF + validation + session security
  -> registration/login/logout -> profile
  -> authenticated comment creation -> ownership checks -> optional moderation queue
  -> programme catalogue -> authenticated registration -> “my registrations”/cancellation

Stable user-visible states and test data
  -> unit tests -> integration tests -> Selenium page objects + explicit-wait journeys
  -> reports/screenshots with executed/unavailable labels -> Quality Lab/viva cards
```

Key rules implied by the graph:

- Comments and programme registration require authentication; profile views require ownership checks.
- The chart must depend on a data-status/provenance contract, not directly on an unreliable external response.
- Quality Lab evidence is downstream of stable public and authenticated flows; it cannot honestly be completed first.
- Accessibility is cross-cutting and must be built into templates/forms before Selenium page objects harden selectors and journeys.

## MVP Recommendation

Prioritize in this order:

1. **Trusted public-information baseline** — responsive layout; dashboard with clearly scoped seeded/fetched statistics, chart summary, provenance, honest fallback/error states, and source-linked plain-language guidance.
2. **Secure account and ownership baseline** — registration, login/logout, CSRF, validation, throttling, profile, and password-change safeguards.
3. **Bounded participation** — programme catalogue/registration and simple community comments with safe output, ownership checks, and a `pending/approved` data model.
4. **Quality Lab and evidence** — unit/integration coverage, Selenium 4 page-object journeys with explicit waits, screenshots, pytest HTML report, and a UI/documentation surface that distinguishes executed evidence from unavailable infrastructure.
5. **Only then, selective differentiators** — provenance panel polish, moderator queue, and viva traceability cards.

Defer: live data automation beyond one conservative server-side adapter; multi-country comparison; notifications; password recovery; MFA; uploads; replies; messaging; payments; and all clinical workflows. They do not improve the core learning outcome enough to justify their security, operational, or safety cost.

## Sources

All source-derived claims below are **MEDIUM confidence** because the configured `classify-confidence` seam rated the verified web-search provider MEDIUM. These are primary/official sources used to form the scope, not instructions for the application.

- [WHO COVID-19 dashboard: data sources and interpretation caveats](https://data.who.int/dashboards/covid19/data) — MEDIUM
- [WHO COVID-19 dashboard: about and reporting frequency](https://data.who.int/dashboards/covid19/about) — MEDIUM
- [CDC: About respiratory illnesses](https://www.cdc.gov/respiratory-viruses/about/index.html) — MEDIUM
- [CDC: Treatment of respiratory viruses](https://www.cdc.gov/respiratory-viruses/treatment/) — MEDIUM
- [CDC: Plain-language materials and resources](https://www.cdc.gov/health-literacy/php/develop-materials/plain-language.html) — MEDIUM
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html) — MEDIUM
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html) — MEDIUM
- [OWASP Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html) — MEDIUM
- [Selenium: Page Object Models](https://www.selenium.dev/documentation/test_practices/encouraged/page_object_models/) — MEDIUM
- [Selenium: Waiting Strategies](https://www.selenium.dev/documentation/webdriver/waits/) — MEDIUM
- [pytest plugin list: pytest-html](https://docs.pytest.org/en/stable/reference/plugin_list.html) — MEDIUM
