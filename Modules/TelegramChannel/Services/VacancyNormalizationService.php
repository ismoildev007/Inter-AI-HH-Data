<?php

namespace Modules\TelegramChannel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VacancyNormalizationService
{
    /**
     * Normalize a raw vacancy text into a structured array using OpenAI.
     * - Keeps original language (no translation)
     * - Extracts title, company, contacts, description, and category
     */
    public function normalize(string $rawText, string $sourceUsername, int $messageId): array
    {
        $model = config('telegramchannel.openai_model', env('OPENAI_MODEL', 'gpt-4.1-nano'));
        $apiKey = config('telegramchannel.openai_key', env('OPENAI_API_KEY'));

        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $prompt = <<<PROMPT
You are an assistant that cleans and standardizes job vacancy posts into a fixed JSON schema. Always read the full title and description before you classify the vacancy.

Rules:
- Output ONLY valid JSON. No extra text, no markdown, no comments.
- Do NOT translate. Keep the same language as in the input.
- Do NOT drop any job-related information.
- Remove stickers, ads, hashtags, channel signatures, unrelated links.
- Use the entire vacancy context (role title, responsibilities, requirements, tools, seniority, department) when choosing a category.
- Never invent new category names. If you are unsure, pick "Other".

Allowed categories (use these exact strings; examples in parentheses are helpers, not additional output):
- Marketing and Advertising (brand marketing, SMM, digital ads, growth marketing)
- Sales and Customer Relations (B2B/B2C sales, account executive, client manager, presales)
- IT and Software Development (backend, frontend, fullstack, mobile, game dev, web dev)
- Data Science and Analytics (BI analyst, data scientist, ML engineer, analytics engineer)
- Product and Project Management (product manager, product owner, scrum master, PMO)
- QA and Testing (manual QA, automation QA, test engineer, QA lead)
- DevOps and Cloud Engineering (DevOps, SRE, platform engineer, cloud engineer)
- Cybersecurity (security analyst, SOC, pentester, AppSec engineer)
- UI/UX and Product Design (UI/UX designer, product designer, design lead, UX researcher)
- Content and Copywriting (copywriter, content strategist, editor, journalist)
- Video and Multimedia Production (video editor, motion designer, cinematographer, 3D artist)
- Photography (photographer, photo editor, retoucher)
- Human Resources and Recruitment (HRBP, recruiter, talent acquisition, HR manager)
- Finance and Accounting (financial analyst, accountant, controller, auditor)
- Banking and Insurance (loan officer, credit analyst, underwriter, insurance agent)
- Legal and Compliance (lawyer, legal counsel, compliance officer, contract specialist)
- Administration and Office Support (office manager, administrator, executive assistant)
- Education and Training (teacher, tutor, lecturer, coach, mentor)
- Healthcare and Medicine (doctor, nurse, medical specialist, clinic staff)
- Pharmacy (pharmacist, pharmacy manager, pharmaceutical assistant)
- Dentistry (dentist, dental technician, orthodontist)
- Veterinary Care (veterinarian, vet technician, zoo specialist)
- Manufacturing and Industrial Engineering (industrial engineer, production engineer, process engineer)
- Mechanical and Maintenance Engineering (mechanic, maintenance engineer, HVAC, MEP)
- Electrical and Electronics Engineering (electrical engineer, electronics engineer, embedded engineer)
- Construction and Architecture (civil engineer, architect, site manager, BIM specialist)
- Logistics and Supply Chain (supply chain manager, planner, operations manager)
- Warehouse and Procurement (warehouse manager, storekeeper, buyer, procurement specialist)
- Transportation and Driving (driver, courier, dispatcher, fleet coordinator)
- Customer Support and Call Center (support specialist, contact center agent, helpdesk)
- Hospitality and Tourism (hotel staff, travel consultant, tour guide, concierge)
- Food and Beverage Service (chef, cook, barista, waiter, restaurant manager)
- Retail and E-commerce (store manager, merchandiser, e-commerce specialist, cashier)
- Real Estate (realtor, broker, property manager, leasing consultant)
- Beauty and Personal Care (cosmetologist, hair stylist, barber, spa specialist)
- Sports and Fitness (fitness trainer, sports coach, physiotherapist, yoga instructor)
- Agriculture and Farming (farmer, agronomist, horticulture specialist, livestock expert)
- Other (if none of the above categories logically fits)

Schema:
{
  "language": "uz|ru|en|...",
  "title": "string",
  "company": "string",
  "contact": {
    "phones": ["+998...", "..."],
    "telegram_usernames": ["@user", "@user2"]
  },
  "description": "string",
  "category_raw": "string",  // free-text short category inferred from the role (e.g., "Sales Manager", "Backend", "Courier")
  "category": "string"       // one of the allowed categories listed above (exact casing)
}

Field rules:
- language: detect from the text.
- title: must be only the role/position. No generic words like "Vakansiya" or "Xodim kerak". If not explicit, infer from stack/skills.
- company: use company/brand name if written, else "".
- contact:
  - phones: extract all phone numbers, normalize by removing spaces/dashes/() only. Keep leading "+" if present. Deduplicate.
  - telegram_usernames: extract all "@user" and "t.me/user" → "@user". Lowercase. Deduplicate.
- description:
  - Put ALL remaining vacancy information (responsibilities, requirements, skills, salary, currency, bonuses, schedule, shift, format, contract, trial period, experience, location, deadlines, how to apply, preferred contact method/time, languages, start date, etc.).
  - Preserve numbers and currency exactly as in text.
  - Write neatly with proper punctuation, commas, spaces, and line breaks.
- category/category_raw:
  - First, set category_raw to a short human label (e.g., "Backend Node.js", "SMM Specialist", "Courier").
  - Then map to the closest category from the allowed list and set category (exact casing). If nothing fits confidently, use "Other".
  - Double-check that the category reflects the key duties/tools from the description, not only the job title.

Input text:
"""
{$rawText}
"""

Context (do not include in output): source_username={$sourceUsername} message_id={$messageId}
PROMPT;

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
            ]);

        if ($response->failed()) {
            Log::error('Vacancy normalization failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('OpenAI request failed: ' . $response->status());
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
