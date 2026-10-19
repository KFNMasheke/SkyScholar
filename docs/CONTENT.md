# Content authoring and publication

The demo bank is functional test content. The aviation bank starts empty. No commercial provider content is bundled.

## Format

Use `local/skyscholar/content/demo.json` as the exact JSON shape. The importer accepts at most 1000 questions / 5 MiB per file. Question IDs are unique within a track and stable across imports. `correct` is a zero-based answer index. Supply 2–6 distinct options and an explanation for every option. All input is plain text, not trusted HTML.

Required fields: `id`, `topic`, `objective`, `stem`, `options`, `correct`, `feedback`, `explanation`, `source`, `status`, `reviewer`, `review_date`.

`status` is `draft` or `approved`. Approved questions require a non-empty reviewer and real date in `YYYY-MM-DD` format, no later than today. A source may be a precise document/section citation, not just a URL. Record the edition/version in the source field. Only a trusted operator with server access runs imports; entering a reviewer's name is a declaration, not an independent approval workflow.

Drafts are stored as Moodle draft question versions. Changed imports create new native versions of the same question bank entry. Unchanged imports are skipped. A question cannot move to another topic under the same ID. Imports are locked and transactional; a malformed file is rejected before mutation. Importing a draft version of a formerly approved question removes it from the ready pool. If this makes a published track undersized, the importer hides that course; restore a sufficient approved bank and explicitly publish again. Review published banks after any such change.

## Review process

1. Map each subject and objective to the current regulator syllabus. Initial South African subjects are provisional organisational categories, not a claim of exact SACAA coverage.
2. Write original questions and explanations, including why distractors are wrong.
3. Identify a current authoritative reference and relevant section.
4. Have a competent instructor check technical accuracy, ambiguity, calculations, local rules and copyright provenance.
5. Mark reviewed content approved, import, preview in Moodle and check random-selection coverage.
6. Run readiness and publish explicitly.

```bash
php local/skyscholar/cli/manage.php --action=import --file=/private/za-reviewed.json
php local/skyscholar/cli/manage.php --action=readiness --track=za-remote
php local/skyscholar/cli/manage.php --action=publish --track=za-remote
```

Publication requires 20 latest approved, ready questions per configured aviation subject (140 total). This is a **software minimum**, not a sufficient final bank or proof of regulatory coverage. Practice draws 10 and mocks draw 20 per subject. The configured 30-minute duration and 75% study target are provisional study settings, not official exam rules.

Question counts/durations are currently configured in `content/tracks.json` before provisioning. Existing quizzes are deliberately not rewritten by a repeated bootstrap or publication. To change an attempted quiz's rules, use Moodle's quiz settings or a new explicitly versioned activity after reviewing the impact on learners. Do not delete historical attempts as an upgrade technique.

## Reuse and independence

Moodle's native question-bank export can export your authorised questions in Moodle XML. Use an explicit redistribution licence and preserve references and copyright notices. Publicly accessible source documents are not automatically licensed for wholesale reproduction. Do not copy a commercial exam bank or use recalled confidential examination items. The SkyScholar name must not imply regulator approval or issuance of a pilot licence.

Report an issue from the attempt revision page. Reports are private, limited to 20 new items per learner per hour, and deduplicated by learner/attempt/question position. Managers resolve them from SkyScholar readiness. Resolution does not silently change an answer: create and review a new content version separately.
