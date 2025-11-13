<?php

namespace Modules\Resumes\Services;

use App\Models\CareerTrackingPdf;
use App\Models\Resume;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Spatie\Browsershot\Browsershot;

class ResumePdfService
{
  public function pdf(Resume $resume): void
  {
    try {
      $existing = CareerTrackingPdf::where('resume_id', $resume->id)->first();
      if ($existing) {
        Log::info("⚠️ Career tracking already exists for resume ID {$resume->id}, skipping...");
        return;
      }

      $resumeText = (string) ($resume->parsed_text ?? $resume->description);

      $prompt = <<<PROMPT
                You are a senior career analyst specialized in interpreting resumes and generating structured career diagnostics.

                Your task:  
                Given a resume, you must deeply analyze it and reconstruct a full 8-section career report with maximum accuracy.

                IMPORTANT — You must understand the resume as follows:
                - Work experience determines technical level (Junior/Middle/Senior).
                - Responsibilities, not years, define level.
                - Keywords such as “CI/CD”, “RBAC”, “testing”, “architecture”, “database design” signal Middle-level maturity.
                - Missing fields must be inferred logically from context, not left empty.
                - All explanations, descriptions, comments, roadmap goals must be detailed and expanded logically.
                - You must preserve narrative parts (long sentences and conclusions) inside JSON fields.
                - information should be in uzbek language of all times.

                Output MUST be valid JSON only.

                ----------------------------------------------------
                ANALYSIS INSTRUCTIONS (HOW YOU MUST THINK):

                1. **General Profile**
                  - Extract name, age, location, languages.
                  - Extract companies AND describe each role’s essence (what person *actually did*).
                  
                2. **Career Diagnostics**
                  - Determine level (Junior / Middle / Senior) based on:
                      * autonomy
                      * complexity of tasks
                      * DevOps responsibility
                      * architecture knowledge
                      * CI/CD usage
                      * testing experience
                  - Explain strengths and growth points in full sentences.

                3. **Hard Skills**
                  - Score 1–10 based on:
                      * real production usage
                      * seniority of tasks
                      * maturity
                      * coverage depth
                  - Add clear comments.

                4. **Roadmap (12 months)**
                  - Every block (1–3, 4–6, 7–9, 10–12) MUST contain:
                      * goal (big objective)
                      * 4–8 detailed tasks
                      * expected outcome (1 paragraph)

                5. **AI Recommendations**
                  - Provide 5–10 clear actionable recommendations.

                6. **Career Potential**
                  - Predict:
                      * readiness for Middle/Senior
                      * time to reach next level
                      * target salary
                      * target market roles

                7. **International Tech Focus**
                  - Extract technologies relevant to EU/GCC/Remote market.

                8. **Final Summary**
                  - A long professional conclusion (~5–8 sentences).

                    Based on this example, I thoroughly researched the person in this resume and developed a career path based on this example:
                    "
                   🧠 Общий профиль
                    Имя: Пулатов Шахбоз Фарход угли
                    Возраст: 25 лет
                    Город: Ташкент
                    Позиция: Vue.js Frontend Developer
                    Опыт: 4 года 8 месяцев
                    Компании:

                    🏢 Asialuxe — Vue.js Frontend Developer (текущая позиция, более 2 лет)

                    💼 Zakiy IT Company — Full-stack Developer (Vue + Node.js, управление командой)

                    👨‍💻 Serius Team, BA Tech Academy, UIC Group — фронтенд-разработка на Vue.js
                    Образование:

                    Tashkent University of Information Technology, Software Engineering
                    Языки: 🇺🇿 Узбекский — Родной 🇬🇧 Английский — B2 🇷🇺 Русский — A2

                    ⚙️ Карьерная диагностика (точка A)
                    Параметр  Оценка
                    🧭 Уровень  Middle+/Senior Frontend Developer
                    💻 Технологии  Vue.js, Nuxt.js, TypeScript, Tailwind, GraphQL, Pinia, Node.js
                    🧩 Архитектура  Уверенно владеет компонентной архитектурой, оптимизацией UI
                    ☁️ Full-stack понимание  Есть опыт Node.js + Prisma + PostgreSQL
                    🧠 Сильные стороны  Опыт управления командой, больше 30 продакшн-проектов
                    ⚠️ Зоны роста  Архитектура Frontend-приложений (Design Patterns), тестирование, CI/CD
                    💬 Soft Skills  Уверенная коммуникация, самостоятельность, зрелое мышление
                    💡 Вывод

                    Шахбоз — сильный middle+/пред-сеньорный фронтенд-инженер, у которого есть опыт end-to-end разработки, лидерства и работы в продакшн-командах.
                    Он обладает технической зрелостью и опытом масштабных B2B-проектов (Asialuxe, CRM, корпоративные панели).

                    Следующий этап — переход от “feature developer” к frontend-архитектору / team lead, с упором на проектирование, DevOps и code quality culture.

                    📊 Навыковая оценка (по 10-балльной шкале)
                    Навык  Уровень  Комментарий
                    Vue.js / Nuxt.js  8.5 / 10  Глубокие знания, опыт крупных SPA-приложений
                    TypeScript  7.5 / 10  Хорошая база, стоит глубже использовать типизацию компонентов
                    State Management (Vuex / Pinia)  8 / 10  Отличный контроль состояния, можно усилить через архитектурные шаблоны
                    GraphQL / REST API  7.5 / 10  Реальный опыт интеграций, стоит освоить caching стратегии
                    Node.js / Backend  6.5 / 10  Базовый уровень, пригоден для full-stack задач
                    Testing (Jest, Cypress)  5 / 10  Мало упоминаний — нуждается в практике unit и e2e тестов
                    Performance / Optimization  7 / 10  Хорошо владеет оптимизацией UI, стоит изучить SSR и lazy hydration
                    Leadership / Teamwork  8 / 10  Руководил фронтенд-командой, опыт управления задачами
                    🧭 Карьерный трек (12 месяцев развития)
                    🎯 Цель:

                    Перейти из Middle+/Pre-Senior → Senior Frontend Architect / Lead Developer
                    с доходом $2500+ (remote или крупная компания) в течение года.

                    🔹 Месяцы 1–3 — “Архитектура и качество”

                    Цель: выйти за рамки “фичей” и проектировать системы.

                    Освоить Vue 3 Composition API patterns (Scoped slots, Composables).

                    Применить SOLID и DRY принципы во фронтенде.

                    Начать писать unit-тесты (Jest) и e2e (Cypress).

                    Изучить архитектуру Nuxt 3 SSR + API routes.

                    📈 Результат: системное мышление и чистый архитектурный подход.

                    🔹 Месяцы 4–6 — “Техническое лидерство”

                    Цель: развить ответственность за команду и продукт.

                    Настроить CI/CD pipeline (GitHub Actions).

                    Создать frontend architecture guide для команды (структура, именование, code review).

                    Провести внутренние воркшопы “Code quality” и “Vue performance”.

                    Начать pet-проект с open-source архитектурой.

                    📈 Результат: лидерский статус в команде и осознанная архитектура.

                    🔹 Месяцы 7–9 — “Fullstack гибкость и DevOps”

                    Цель: увеличить независимость как инженера.

                    Изучить Docker, Nginx, basic AWS (S3, EC2).

                    Реализовать pet-проект: Vue + Node.js + Prisma + PostgreSQL.

                    Добавить GraphQL caching и SSR оптимизацию.

                    📈 Результат: готовность к ролям “Lead Frontend” и “Fullstack Architect”.

                    🔹 Месяцы 10–12 — “Senior / Lead позиционирование”

                    Цель: построить публичный имидж специалиста.

                    Создать портфолио на GitHub/LinkedIn (3 топовых проекта).

                    Написать 2 статьи:

                    “Vue3 Enterprise Architecture Guide”

                    “Optimizing Nuxt Apps for Performance and SEO”

                    Подготовиться к AI-интервью уровня Senior в inter-ai.

                    📈 Результат: готовность к руководящей позиции и международным проектам.

                    💬 Рекомендации AI

                    💎 Сфокусируйся на Frontend Architecture & Testing — это твой путь к Senior.

                    🧠 Изучи design patterns во Vue/Nuxt и SSR-нагрузку.

                    Baxrom aka, [11/11/25 1:42 PM]
                    🧩 Настрой CI/CD и Docker окружение для всех своих pet-проектов.

                    📘 Развивай навык code review и наставничество в команде.

                    🌍 Продолжай повышать английский до C1 — для remote и лид-ролей.

                    💰 Прогноз и потенциал
                    Метрика  Значение
                    Текущий уровень  Middle+
                    Потенциал роста  9.5 / 10
                    Hard Skills  8.4 / 10
                    Soft Skills  8.0 / 10
                    Senior Readiness  75 %
                    Время до Senior  9–12 месяцев
                    Целевая роль  Senior Frontend Architect / Lead Developer
                    Целевая зарплата  $2500–3000+ (Remote / GCC / EU)
                    🧩 Tech Focus для интернациональных проектов
                    Направление  Ключевые навыки
                    Frontend Core  Vue3, Nuxt3, TypeScript, SSR
                    State Mgmt  Pinia, Composition API, GraphQL cache
                    Architecture  Modular UI, Atomic Design, Clean Frontend
                    DevOps  Docker, GitHub Actions, CI/CD
                    Testing  Jest, Cypress, Vitest
                    Performance  Code-splitting, hydration, lazy loading
                    🧭 Итог

                    Шахбоз — зрелый middle+/пред-сеньорный фронтенд-инженер, способный вести команду,
                    строить сложные интерфейсы и держать высокий уровень кода.
                    При развитии архитектурных навыков и внедрении DevOps-стека,
                    он может стать Frontend Lead / Architect уровня international remote к середине 2026 года.
                "

                Analyze the following resume text and produce a structured JSON with the following fields:
                {
                  "general_profile": {
                    "name": "",
                    "age": "",
                    "city": "",
                    "position": "",
                    "experience_text": "",
                    "companies": [],
                    "education": "",
                    "languages": []
                  },

                  "career_diagnostics": {
                    "level": { "level": "", "comment": "" },
                    "technologies": { "technology": "", "comment": "" },
                    "architecture_score": { "score": "", "comment": "" },
                    "architecture_comment": { "score": "", "comment": "" },
                    "fullstack_score": { "score": "", "comment": "" },
                    "fullstack_comment": "",
                    "strengths": [],
                    "growth_zones": [],
                    "soft_skills_score": { "score": "", "comment": "" },
                    "portrait_summary": ""
                  },
                  "next_level": "",

                  "hard_skills_rating": {
                    "php_laravel": { "score": "", "comment": "" },
                    "mysql_postgresql": { "score": "", "comment": "" },
                    "rest_api": { "score": "", "comment": "" },
                    "testing": { "score": "", "comment": "" },
                    "ci_cd": { "score": "", "comment": "" },
                    "linux_ssh": { "score": "", "comment": "" },
                    "architecture_patterns": { "score": "", "comment": "" },
                    "devops_basics": { "score": "", "comment": "" },
                    "soft_skills": { "score": "", "comment": "" }
                  },

                  "growth_roadmap_12_months": {
                    "months_1_3": {
                      "goal": "",
                      "tasks": [],
                      "result": ""
                    },
                    "months_4_6": {
                      "goal": "",
                      "tasks": [],
                      "result": ""
                    },
                    "months_7_9": {
                      "goal": "",
                      "tasks": [],
                      "result": ""
                    },
                    "months_10_12": {
                      "goal": "",
                      "tasks": [],
                      "result": ""
                    }
                  },

                  "ai_recommendations": [],

                  "career_potential": {
                    "current_level": "",
                    "growth_potential_score": "",
                    "hard_skill_average": "",
                    "soft_skill_average": "",
                    "middle_readiness_percent": "",
                    "time_to_middle_months": "",
                    "target_role": "",
                    "salary_local": "",
                    "salary_remote": ""
                  },

                  "international_tech_focus": [],

                  "final_summary": ""
                }
                Here is the resume:
                <<<RESUME_START>>>
                {$resumeText}
                <<<RESUME_END>>>
                ONLY RETURN JSON. 
                NO TEXT OUTSIDE JSON.
                NO MARKDOWN.

                PROMPT;

      $model = env('OPENAI_MODEL', 'gpt-5-nano');

      $response = Http::withToken(env('OPENAI_API_KEY'))
        ->timeout(120)
        ->post('https://api.openai.com/v1/chat/completions', [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful AI for analyzing resumes.'],
            ['role' => 'user', 'content' => $prompt],
          ],
        ]);
      Log::info('Response yo umuman', json_decode($response->body(), true));

      $result = $response->json();
      $jsonOutput = $result['choices'][0]['message']['content'] ?? null;

      // JSON ni tozalash
      $jsonOutput = preg_replace('/```json\s*|\s*```/', '', $jsonOutput);
      $decoded = json_decode($jsonOutput, true);

      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

        // Default qiymatlar qo'shish
        if (!isset($decoded['contact'])) {
          $decoded['contact'] = ['email' => '---', 'phone' => '---'];
        }
        if (!isset($decoded['career_forecast'])) {
          $decoded['career_forecast'] = [
            'senior_readiness' => 0,
            'hard_skills' => 0,
            'potential_level' => 0
          ];
        }

        // $pdfFileName = 'career_report_' . $resume->id . '_' . time() . '.pdf';
        // $pdfPath = 'career_reports/' . $pdfFileName;
        // $imagePath = public_path('tracking/assets/Logo.svg');

        // $imageData = base64_encode(file_get_contents($imagePath));
        // $imageSrc = 'data:image/svg+xml;base64,' . $imageData;
        // //                $pdf = SnappyPdf::loadView('careerTracking.tracking', [
        // //                    'data' => $decoded,
        // //                    'logo' => $imageSrc,
        // //                ])->setOption('enable-local-file-access', true)
        // //                    ->setOption('margin-top', 0)
        // //                    ->setOption('margin-right', 0)
        // //                    ->setOption('margin-bottom', 0)
        // //                    ->setOption('margin-left', 0)
        // //                    ->setOption('page-size', 'A4')
        // //                    ->setOption('encoding', 'UTF-8');
        // $pdfBinary = Browsershot::html(
        //   view('careerTracking.tracking', [
        //     'data' => $decoded,
        //     'logo' => $imageSrc,
        //   ])->render()
        // )
        //   ->format('A4')
        //   ->margins(0, 0, 0, 0)
        //   ->noSandbox() // Linux serverlarda kerak bo‘ladi
        //   ->waitUntilNetworkIdle() // rasmlar to‘liq yuklansin
        //   ->pdf(); // ❗️ pdf() bu binary qaytaradi

        // // PDF faylni storage/public ichiga yozamiz
        // Storage::disk('public')->put($pdfPath, $pdfBinary);

        CareerTrackingPdf::updateOrCreate(
          ['resume_id' => $resume->id],
          [
            'json' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            // 'pdf' => $pdfPath,
          ]
        );
      } else {
        Log::error('Invalid JSON from OpenAI for resume ID: ' . $resume->id, [
          'response' => $jsonOutput,
        ]);
      }
    } catch (\Throwable $e) {
      Log::error('Error generating career PDF for resume ID: ' . $resume->id, [
        'message' => $e->getMessage(),
      ]);
    }
  }
}
