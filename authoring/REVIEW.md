# Authoring and review gate

Authoring status is separate from publishing status. Draft files are not evidence of instructor approval.

1. Reconcile catalogue identity and current terminology. Keep missing legacy IDs null.
2. Map each outcome and question to a current authoritative document and exact section. Label provisional coverage.
3. Review technical correctness, local rules, units, ambiguity, distractors, copyright provenance and accessibility.
4. Pilot the lesson and assess whether the exercise demonstrates the stated outcome.
5. A competent reviewer supplies their identity and real review date only after review. Authors and tools must not invent this evidence.
6. Import draft JSON using `manage.php --action=import --file=/private/za-foundations.draft.json`. Draft versions stay outside the ready pool. Reimport reviewed versions using the same stable IDs.
7. Run readiness, preview native quizzes and explicitly publish only after content and live-host gates pass.

The draft bank starts with five items per subject. The current plugin needs 20 approved ready items per configured subject just to meet its software publication minimum. Five drafts are not a complete bank, not an official syllabus map and not sufficient for publication.

The XML exporter writes a review pack whose questions are named DRAFT / DO NOT PUBLISH. Moodle XML does not preserve the plugin's draft-version and reviewer enforcement. Never use it as a production publication path. Import only into a hidden staff-only review course if needed, and verify permissions first.

No Moodle installation, existing attempts, enrolments or live configuration is changed by this authoring work.
