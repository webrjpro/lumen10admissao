# Changelog

## 1.3.0 — 2026-11-09 (2026110900)

- Replace the English-to-Portuguese fallback with a complete English language pack; localise forms, pages, settings, notifications and PDF labels.
- Move progressive browser features to a standard compiled AMD module with a source map; retain native server-side form validation.
- Use Mustache for scope, private identity details and document presentation. Add semantic row headers, a named document dialog and visible keyboard focus.
- Remove duplicate manual stylesheet loading and the legacy global JavaScript file.
- Record authenticated views using Moodle's native course module viewed event without treating views as admission approval.
- Allow applicants without course catalogue browsing permission to read the explicitly published admission category name; reject public applications when reception/category is hidden.
- Document personal fields explicitly through the Privacy API and batch staff history actor queries.
- Require the optional Student Record plugin to be installed before invoking its archival hook. Retain a native reception course fallback.
- Make the medical-report regression test safe when the optional archive table is absent.
- Accept identical scan filenames in separate document fields while retaining native upload origin/error checks, MIME/extension validation and size limits.
- Fit public forms within both single-column and two-column Moodle login layouts.
- Correct the obsolete temporary-password error text and use native translated date formats.
- Add complete PHP GPL notices, English documentation, dependency/installation guidance and meaningful regression coverage.
- Declare Moodle 5.1 and 5.2 support. Preserve existing installer floor, accounts, identifiers, issued documents and workflow data.

There is no required auxiliary plugin and no schema change in this release. Marketplace approval is not implied.
