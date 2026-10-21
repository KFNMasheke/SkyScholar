# SkyScholar authoring workspace

This is course-authoring work, independent of the Moodle installation.

Started: 20 October 2026. **Drafts only. Nothing in this directory is approved or published.**

## What is authored

- 14 recoverable historical core course titles, each with a blueprint, outcomes, four-module sequence, original opening lesson, desk exercise and marking guide.
- Seven opening subject notes matching the current provisional South African exam-track topics.
- Original draft practice items with option-level feedback, explanations and source/review fields.
- A validator and review-pack exporter that work without Moodle.

`catalogue.json` is a flat array using only the eight previously agreed category names. Its `draft-*` identifiers are local authoring keys, not reconstructed SKS IDs. These are not complete courses: later module lessons, practical assets, full banks and expert reviews remain outstanding.

## Catalogue reconciliation

Earlier work referred to a later 36-course SKS catalogue. The exact list and identifiers have not been recovered. Do not invent the missing titles, IDs, durations or prices. Reconcile the source catalogue before claiming full catalogue coverage. Preserve the current free-practice commitment: no price or paywall is introduced by these drafts.

## Review pack

Run `python scripts/review_content.py` from the repository root. It validates the authoring catalogue and draft bank and builds a local review-only Moodle XML file. Importing that XML directly would bypass the plugin's review metadata workflow; keep it out of learner courses. Use the plugin JSON importer for versioned draft imports once Moodle is ready. Read REVIEW.md first.

## Suggested authoring order

1. Review the seven foundations and source map with a competent instructor.
2. Expand the 14 opening lessons into complete modules and original exercises.
3. Reconcile the later catalogue and author its remaining courses.
4. Build adequate objective coverage, pilot the lessons and check accessibility.
5. Approve reviewed versions, import through the plugin and publish only after readiness and live-host checks pass.

## Expanded self-study edition (21 October 2026)

`full-courses/` contains 15 foundational self-study sequences (64 units), with
worked examples, original illustrations, exercises/model answers, 128 checks and
15 final projects. `radiotelephony/scenarios.json` supplies 27 fictional practice
scenarios. `references-v2.json` records checked primary-source entry points and
verification needs. Technical and instructor review remain pending; no reviewer
or authority approval is invented. These are not a verified complete licensing
syllabus or approved practical training. The older source files remain for
provenance; the edition-2 study guide defines the expanded sequence.

Run `python3 scripts/validate_curricula.py`, `python3 scripts/build_course_content.py`
and `node tests/radio_test.js`. Artwork is reproducible with Pillow using
`scripts/render_course_art.py`; deployed Moodle needs no Python or Node.
