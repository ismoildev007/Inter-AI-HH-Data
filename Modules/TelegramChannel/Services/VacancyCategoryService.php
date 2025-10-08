<?php

namespace Modules\TelegramChannel\Services;

class VacancyCategoryService
{
    private array $canonical = [
        'marketing and advertising',
        'sales and customer relations',
        'it and technology',
        'finance and accounting',
        'administrative and office management',
        'human resources',
        'education and training',
        'design and creative',
        'logistics and transportation',
        'manufacturing and engineering',
        'healthcare and pharmaceuticals',
        'legal and law',
        'hospitality and tourism',
        'construction and architecture',
        'other',
    ];

    public function normalize(?string $category, ?string $title = '', ?string $description = '', ?string $fallbackRaw = ''): string
    {
        $candidate = $this->fromString($category);
        if ($candidate) {
            return $candidate;
        }

        $candidate = $this->fromString($fallbackRaw);
        if ($candidate) {
            return $candidate;
        }

        return 'Other';
    }

    private function fromString(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        $key = $this->normalizeKey($v);

        foreach ($this->canonical as $canon) {
            if ($this->normalizeKey($canon) === $key) {
                return $this->formatCanonical($canon);
            }
        }

        return null;
    }

    private function normalizeKey(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace(['&', '+'], 'and', $value);
        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }

    private function formatCanonical(string $value): string
    {
        $value = strtolower($value);
        $parts = preg_split('/\s+/', $value);
        $lowercaseWords = ['and', 'or', 'of', 'the', 'in'];

        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }

            if ($part === 'it') {
                $parts[$i] = 'IT';
                continue;
            }

            if ($i > 0 && in_array($part, $lowercaseWords, true)) {
                $parts[$i] = strtolower($part);
                continue;
            }

            $parts[$i] = ucfirst($part);
        }

        return implode(' ', $parts);
    }

    private function inferFromText(string $title, string $description): ?string
    {
        return null;
    }
}
