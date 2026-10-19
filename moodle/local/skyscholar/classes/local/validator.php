<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_skyscholar\local;

/** Plain-text, bounded question format. Validates the whole file before any mutation. */
final class validator {
    public static function decode(string $json, array $tracks): array {
        $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['track'], $data['questions']) || !is_string($data['track']) ||
                !isset($tracks[$data['track']]) || !is_array($data['questions']) ||
                !array_is_list($data['questions']) || count($data['questions']) > 1000) {
            throw new \InvalidArgumentException('Invalid track or question list (maximum 1000).');
        }
        $ids = [];
        foreach ($data['questions'] as $q) {
            if (!is_array($q)) {
                throw new \InvalidArgumentException('Each question must be an object.');
            }
            foreach (['id', 'topic', 'objective', 'stem', 'explanation', 'source', 'status', 'reviewer', 'review_date'] as $key) {
                if (!isset($q[$key]) || !is_string($q[$key]) || strlen($q[$key]) > 10000) {
                    throw new \InvalidArgumentException('Missing or oversized field: ' . $key);
                }
            }
            if (!preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/', $q['id']) || isset($ids[$q['id']]) ||
                    !isset($tracks[$data['track']]['topics'][$q['topic']])) {
                throw new \InvalidArgumentException('Duplicate/invalid question ID or unknown topic.');
            }
            $ids[$q['id']] = true;
            if (!in_array($q['status'], ['draft', 'approved'], true) ||
                    !is_int($q['correct'] ?? null) || !is_array($q['options'] ?? null) ||
                    !array_is_list($q['options']) || count($q['options']) < 2 || count($q['options']) > 6 ||
                    $q['correct'] < 0 || $q['correct'] >= count($q['options']) ||
                    !is_array($q['feedback'] ?? null) || !array_is_list($q['feedback']) ||
                    count($q['feedback']) !== count($q['options'])) {
                throw new \InvalidArgumentException('Invalid status, answer index or answer feedback.');
            }
            foreach (array_merge($q['options'], $q['feedback'], [$q['stem'], $q['explanation'], $q['objective'], $q['source']]) as $text) {
                if (!is_string($text) || trim($text) === '' || strlen($text) > 10000) {
                    throw new \InvalidArgumentException('Question content must contain bounded, non-empty text.');
                }
            }
            if (count(array_unique($q['options'])) !== count($q['options']) || strlen($q['reviewer']) > 255) {
                throw new \InvalidArgumentException('Duplicate answers or oversized reviewer.');
            }
            if ($q['status'] === 'approved') {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $q['review_date']);
                if (trim($q['reviewer']) === '' || !$date || $date->format('Y-m-d') !== $q['review_date'] ||
                        $date->format('Y-m-d') > date('Y-m-d')) {
                    throw new \InvalidArgumentException('Approved content needs a reviewer and valid past/current review date.');
                }
            }
        }
        return $data;
    }
}
