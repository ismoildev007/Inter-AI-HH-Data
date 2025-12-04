<?php

namespace Modules\TelegramChannel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\TelegramChannel\Support\ContentFingerprint;

class VacancyNormalizationService
{
  /**
   * Normalize a raw vacancy text into a structured array using OpenAI.
   * - Keeps original language (no translation)
   * - Extracts title, company, contacts, and description
   */
  public function normalize(string $rawText, string $sourceUsername, int $messageId): array
  {
    $model = config('telegramchannel.openai_model', env('OPENAI_MODEL', 'gpt-4.1-nano'));
    $apiKey = config('telegramchannel.openai_key', env('OPENAI_API_KEY'));

    if (!$apiKey) {
      throw new \RuntimeException('OPENAI_API_KEY is not configured.');
    }

    // Build strict category list from our canonical categories (INCLUDING "Other")
    try {
      /** @var \Modules\TelegramChannel\Services\VacancyCategoryService $catSvc */
      $catSvc = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
      $all = $catSvc->getCanonicalCategories(); // slug => label
      $allowedCategoryLabels = array_values($all); // labels only, including 'Other'
    } catch (\Throwable $e) {
      $allowedCategoryLabels = [];
    }
    $allowedCategoriesJson = json_encode($allowedCategoryLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $prompt = <<<PROMPT
You are an assistant that cleans and standardizes job vacancy posts into a fixed JSON schema. Always read the full title and description before you format the vacancy.

Rules:
- Output ONLY valid JSON. No extra text, no markdown, no comments.
- Do NOT translate. Keep the same language as in the input.
- Do NOT drop any job-related information.
- Remove stickers, ads, hashtags, channel signatures, unrelated links.

Schema:
{
  "title": "string",
  "company": "string",
  "contact": {
    "phones": ["+998...", "..."],
    "telegram_usernames": ["@user", "@user2"]
  },
  "description": "string",
  "category": "string"
}

Field rules:
- title:
  - Must be only the role/position (e.g. "Frontend Developer", "Sales Manager").
  - Remove generic words like "Vakansiya", "Ish kerak", "Xodim kerak".
  - If title is not explicit, infer from stack/skills in the text.
- company:
  - Use company/brand name if written, else "".
- contact:
  - phones: extract all phone numbers; normalize by removing spaces, dashes, parentheses only; keep leading "+" if present; deduplicate.
  - telegram_usernames: extract all "@user" and "t.me/user" → "@user"; lowercase; deduplicate.
- description:
  - Put ALL remaining vacancy information (responsibilities, requirements, skills, salary, currency, bonuses, schedule, shift, format, contract, trial period, experience, location, deadlines, how to apply, preferred contact method/time, languages, start date, etc.).
  - Preserve numbers and currency exactly as in text.
  - Write neatly with proper punctuation, commas, spaces, and line breaks.

- category (CRITICAL – follow strictly):
  - First read the full title, then read the full description.
  - Decide the category mainly from the title; use the description only to confirm or slightly adjust it.
  - Choose EXACTLY ONE label from the allowed list. Never invent new labels.

  - Map roles to categories as follows. If any of these roles/keywords appears in the title or clearly dominates the description, ALWAYS choose the specified label and NEVER choose "Other" for these cases:
    - "IT and Software Development" → developer, programmer, software engineer, frontend, backend, full‑stack, mobile developer, iOS, Android, web developer, QA, tester, SDET, DevOps, SRE, data engineer, data scientist, ML engineer, IT specialist, software architect, game developer.
    - "Marketing and Advertising" → marketing, marketer, SMM, social media, PR, public relations, brand manager, digital marketing, performance marketing, media buying, SEO, PPC, targetolog.
    - "Sales and Customer Relations" → sales, sotuvchi, sales manager, account manager, client manager, менеджер по работе с клиентами, business development, bizdev, commercial manager, customer success, relationship manager.
    - "Customer Support and Call Center" → call center, contact center, support specialist, support agent, helpdesk, service desk, operator, dispatcher.
    - "Finance and Accounting" → accountant, accounting, buxgalter, finance manager, financial analyst, auditor, controller, tax specialist, treasury.
    - "Human Resources and Recruitment" → HR, human resources, recruiter, talent acquisition, HR manager, HR generalist, HRBP, people partner.
    - "Administration and Office Support" → office manager, administrator, office administrator, assistant, executive assistant, personal assistant, secretary, receptionist.
    - "Logistics and Supply Chain" → logistics, logistician, warehouse, sklad, supply chain, procurement, purchasing, delivery, courier, transport, driver, shipping, customs, freight.
    - "Product and Project Management" → product manager, product owner, product lead, project manager, PM, scrum master.

  - If several labels seem possible, choose the most specific one based on the title (do NOT choose "Other").
  - Use "Other" ONLY IF, after reading the entire title and description, you cannot reasonably assign the vacancy to ANY of the categories above.
  - The category value MUST be exactly one of the labels from the allowed list (case-insensitive match, but output must match exactly).

Allowed categories (labels):
{$allowedCategoriesJson}
Input text:
"""
{$rawText}
"""
Context (do not include in output): source_username={$sourceUsername} message_id={$messageId}

PROMPT;

    $maxConf  = (int) config('telegramchannel_relay.openai.normalization_max_tokens', 9000);
    $hardConf = (int) config('telegramchannel_relay.openai.normalization_hard_cap', 10000);
    $maxTokens = max(1, min($maxConf, $hardConf));

    $response = Http::withToken($apiKey)
      ->timeout(60)
      ->retry(3, fn($attempt) => [500, 2000, 5000][$attempt - 1] ?? 5000)
      ->post('https://api.openai.com/v1/chat/completions', [
        'model' => $model,
        'messages' => [
          ['role' => 'system', 'content' => 'You format vacancies into a fixed JSON schema.'],
          ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.1,
        // Bound output size by config (rich JSON)
        'max_tokens' => $maxTokens,
      ]);

    if ($response->failed()) {
      Log::error('Vacancy normalization failed', ['status' => $response->status(), 'body' => $response->body()]);
      throw new \RuntimeException('OpenAI request failed: ' . $response->status());
    }

    // Optional token usage logging
    if ((bool) config('telegramchannel_relay.metrics.log_usage', true)) {
      try {
        $usage = (array) $response->json('usage', []);
        $finish = (string) $response->json('choices.0.finish_reason', '');
        // Log::info('OpenAI usage (relay)', [
        //   'op' => 'normalization',
        //   'model' => $model,
        //   'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
        //   'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
        //   'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
        //   'max_tokens' => $maxTokens,
        //   'finish' => $finish,
        //   'hash' => ContentFingerprint::raw($rawText),
        //   'source' => $sourceUsername,
        //   'message_id' => $messageId,
        //   'minute' => date('Y-m-d H:i'),
        // ]);
      } catch (\Throwable $e) {
        // best-effort only
      }
    }

    $content = (string) $response->json('choices.0.message.content', '');
    $content = trim($content);
    $content = preg_replace('/^```(json)?/i', '', $content);
    $content = preg_replace('/```$/', '', $content);
    $content = trim($content);

    $data = json_decode($content, true);
    if (!is_array($data)) {
      throw new \RuntimeException('Invalid JSON from OpenAI: ' . $content);
    }

    // Ensure shape
    $normalized = [
      'language' => (string) ($data['language'] ?? ''),
      'title' => (string) ($data['title'] ?? ''),
      'company' => (string) ($data['company'] ?? ''),
      'contact' => [
        'phones' => array_values(array_filter(array_map('strval', (array) ($data['contact']['phones'] ?? [])))),
        'telegram_usernames' => array_values(array_filter(array_map('strval', (array) ($data['contact']['telegram_usernames'] ?? [])))),
      ],
      'description' => (string) ($data['description'] ?? ''),
      'category_raw' => (string) ($data['category_raw'] ?? ''),
      'category' => (string) ($data['category'] ?? ''),
    ];

    return $normalized;
  }
}
