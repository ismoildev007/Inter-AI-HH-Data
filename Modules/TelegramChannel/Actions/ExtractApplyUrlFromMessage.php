<?php

namespace Modules\TelegramChannel\Actions;

class ExtractApplyUrlFromMessage
{
    /**
     * Extract the best external apply/career/vacancy URL from a Telegram message payload.
     * Returns a normalized http(s) URL string or null if none suitable.
     */
    public function handle(array $msg): ?string
    {
        $candidates = [];

        // 1) Entities: textUrl (label-linked) and plain url entities
        $text = (string)($msg['message'] ?? '');
        foreach (['entities', 'caption_entities'] as $ekey) {
            if (!empty($msg[$ekey]) && is_array($msg[$ekey])) {
                foreach ($msg[$ekey] as $ent) {
                    $type = (string)($ent['_'] ?? '');
                    if ($type === 'messageEntityTextUrl' && !empty($ent['url'])) {
                        $candidates[] = (string)$ent['url'];
                    } elseif ($type === 'messageEntityUrl') {
                        // Extract substring as URL using offset/length
                        $off = (int)($ent['offset'] ?? -1);
                        $len = (int)($ent['length'] ?? 0);
                        if ($off >= 0 && $len > 0 && $off + $len <= strlen($text)) {
                            $raw = substr($text, $off, $len);
                            if ($raw !== '') $candidates[] = $raw;
                        }
                    }
                }
            }
        }

        // 2) Inline keyboard buttons with URLs
        if (!empty($msg['reply_markup'])) {
            $rm = $msg['reply_markup'];
            // MadelineProto uses replyInlineMarkup with rows/buttons
            if (!empty($rm['rows']) && is_array($rm['rows'])) {
                foreach ($rm['rows'] as $row) {
                    if (!empty($row['buttons'])) {
                        foreach ($row['buttons'] as $btn) {
                            $url = $btn['url'] ?? null;
                            if (is_string($url) && $url !== '') $candidates[] = $url;
                        }
                    }
                }
            }
            // Some variants may expose "inline_keyboard" directly
            if (!empty($rm['inline_keyboard']) && is_array($rm['inline_keyboard'])) {
                foreach ($rm['inline_keyboard'] as $row) {
                    foreach ((array)$row as $btn) {
                        $url = $btn['url'] ?? null;
                        if (is_string($url) && $url !== '') $candidates[] = $url;
                    }
                }
            }
        }

        // 3) Webpage preview URL
        if (!empty($msg['media']['webpage']['url'])) {
            $candidates[] = (string)$msg['media']['webpage']['url'];
        }

        // 4) Regex fallback from text
        if ($text !== '') {
            if (preg_match_all('/https?:\/\/[\w\-\.\[\]:%#@\/?=&+~;,]+/iu', $text, $m)) {
                foreach ($m[0] as $u) $candidates[] = $u;
            }
        }

        // Normalize and rank
        $normalized = [];
        foreach ($candidates as $u) {
            $u = $this->normalizeUrl($u);
            if ($u === null) continue;
            $normalized[$u] = $this->score($u); // de-dup by key
        }

        if (!$normalized) return null;

        arsort($normalized); // highest score first
        $best = array_key_first($normalized);
        return $best ?: null;
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') return null;
        // Only http(s)
        if (!preg_match('~^https?://~i', $url)) return null;
        // Parse
        $parts = @parse_url($url);
        if (!$parts || empty($parts['host'])) return null;
        $scheme = strtolower($parts['scheme'] ?? 'http');
        if ($scheme !== 'http' && $scheme !== 'https') return null;
        $host = strtolower($parts['host']);

        // SSRF safety: disallow private/loopback hosts (basic)
        if ($this->isDisallowedHost($host)) return null;

        // Remove tracking params
        $query = [];
        if (!empty($parts['query'])) parse_str($parts['query'], $query);
        $query = $this->stripTrackingParams($query);
        $qs = http_build_query($query);

        $norm = $scheme . '://' . $host;
        if (!empty($parts['port'])) $norm .= ':' . $parts['port'];
        $norm .= $parts['path'] ?? '';
        if ($qs !== '') $norm .= '?' . $qs;
        if (!empty($parts['fragment'])) $norm .= '#' . $parts['fragment'];
        return $norm;
    }

    private function isDisallowedHost(string $host): bool
    {
        // Block obvious loopback/local
        $h = strtolower($host);
        if ($h === 'localhost' || $h === '127.0.0.1' || $h === '::1') return true;
        if (preg_match('~^\[(::1)\]$~', $h)) return true; // IPv6 loopback
        // Optional: basic private ranges by hostname are hard; skip resolving DNS
        return false;
    }

    private function stripTrackingParams(array $query): array
    {
        $deny = ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid','yclid','fbclid','_ga','_gl'];
        foreach ($deny as $k) unset($query[$k]);
        return $query;
    }

    private function score(string $url): int
    {
        $score = 0;
        $lower = mb_strtolower($url);

        // Prefer non-Telegram links
        if (!str_contains($lower, 't.me/')) $score += 10;

        // Intent keywords
        foreach (['job','apply','career','vacancy','work','position','details'] as $kw) {
            if (str_contains($lower, $kw)) $score += 3;
        }

        // Known ATS domains
        foreach ([
            'greenhouse.io','lever.co','workable.com','smartrecruiters.com','ashbyhq.com',
            'workday.com','myworkdayjobs.com','teamtailor.com','personio.de','personio.com',
            'bamboohr.com','jobs.lever.co','jobs.workable.com','hh.ru','hh.uz','linkedin.com'
        ] as $dom) {
            if (str_contains($lower, $dom)) $score += 5;
        }

        // Penalize Telegram
        if (str_contains($lower, 't.me/')) $score -= 5;

        return $score;
    }
}

