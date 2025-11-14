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
      // $existing = CareerTrackingPdf::where('resume_id', $resume->id)->first();
      // if ($existing) {
      //   Log::info("⚠️ Career tracking already exists for resume ID {$resume->id}, skipping...");
      //   return;
      // }

      $resumeText = (string) ($resume->parsed_text ?? $resume->description);

      $prompt = <<<PROMPT
                You are a senior career analyst specialized in interpreting resumes and generating structured career diagnostics.

                Your task:
                Given a resume, deeply analyze it and reconstruct a full 8-section career report with maximum accuracy using the EXACT JSON STRUCTURE provided below.

                STRICT RULES:
                - You MUST NOT change the JSON structure, key names, key order, or nesting.
                - All values must be generated based on the resume, but keys must stay exactly identical.
                - All narrative descriptions, comments, diagnostics, summaries, strengths, roadmap texts MUST be written in Uzbek.
                - Missing information must be logically inferred from context.
                - Output MUST be valid JSON only — no extra text, no markdown, no explanations.
                - information should be in uzbek language of all times.


                ----------------------------------------------------
                ANALYSIS LOGIC YOU MUST FOLLOW:

                1. General Profile
                  Extract name, age, city, position, experience, languages, email.
                  Describe companies and what the person actually did in each role.

                2. Career Diagnostics
                  Determine the level (Junior / Middle / Middle+ / Senior) using:
                  autonomy, architecture, CI/CD, testing, full-stack exposure, communication.
                  Provide detailed Uzbek explanations for strengths and growth zones.

                3. Hard Skills
                  Score 1–10 based on depth, real usage, maturity, production experience.
                  Include detailed comments.

                4. Roadmap (12 months)
                  For each block (1–3, 4–6, 7–9, 10–12):
                  - Write a goal
                  - Add 4–8 actionable tasks
                  - Provide a rich result paragraph in Uzbek

                5. AI Recommendations
                  Provide 5–10 concrete professional recommendations.

                6. Career Potential
                  Predict readiness for next level, growth speed, salary expectations.

                7. International Tech Focus
                  Extract skills relevant for EU / GCC / remote market.

                8. Final Summary
                  5–8 sentence career conclusion in Uzbek.

                in general_profile.level language should be in english like Junior, Middle, Senior etc.
                in some part if level giving you should give in enlish language only.

                in skill_radar.competencies, provide scores for like this:
                  "Frontend Development" : "80,
                  "Backend Development" : "75",
                in gap_analysis.skills, provide like this:
                      {
                        "name": "JavaScript",
                        "current": "80",
                        "target": "70",
                        "gap": "50"
                      },
                in career_path, may be more than 3 or less remove it if less than 3 if it empty, if more add. provide like this:
                    {
                      "position": "Frontend Developer",
                      "company": "Tech Solutions",
                      "period": "Jan 2020 - Dec 2022",
                      "experience": "3 years",
                      "status": "Full-time",
                      "description": "Developed user interfaces using React.js and collaborated with designers to enhance UX.",
                      "achievements": [
                        "Led the migration to React.js, improving load times by 30%.",
                        "Implemented a component library that reduced development time by 20%.",
                        "Mentored junior developers, resulting in a more skilled team."
                      ],
                      "tech_stack": ["JavaScript", "React.js", "HTML", "CSS"]
                    },

                Analyze the following resume text and produce a structured JSON with the following fields:
                {
                  "general_profile": {
                    "name": "",
                    "age": "",
                    "city": "",
                    "position": "",
                    "level": "",
                    "experience_text": "",
                    "email": "",
                    "languages": []
                  },

                  "top_metrics": {
                    "hard_skills_score": "",
                    "senior_ready_percent": "",
                    "potential_score": "",
                    "projects_in_production": ""
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

                  "profile_statistics": {
                    "current_level": {
                      "level": "",
                      "comment": ""
                    },
                    "target_salary": {
                      "amount": "",
                      "period": ""
                    },
                    "soft_skills": {
                      "score": "",
                      "comment": ""
                    },
                    "companies_count": {
                      "count": "",
                      "companies": []
                    },
                    "main_stack": {
                      "stack": "",
                      "details": []
                    },
                    "teamlead_experience": {
                      "status": "",
                      "comment": ""
                    },
                    "education": {
                      "university": "",
                      "program": ""
                    },
                    "goal": {
                      "position": "",
                      "direction": ""
                    }
                  },

                  "skills_radar": {
                    "competencies": {},
                    "advice": ""
                  },

                  "detailed_skills": {
                    "items": [
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      },
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      },
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      },
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      },
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      },
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      },
                      {
                        "name": "",
                        "score": "",
                        "level": ""
                      }
                    ],
                    "average_score": "",
                    "senior_progress": "",
                    "senior_progress_percent": ""
                  },

                  "strengths_and_growth": {
                    "strengths": [
                      {
                        "title": "",
                        "impact": "",
                        "description": ""
                      },
                      {
                        "title": "",
                        "impact": "",
                        "description": ""
                      },
                      {
                        "title": "",
                        "impact": "",
                        "description": ""
                      },
                      {
                        "title": "",
                        "impact": "",
                        "description": ""
                      },
                      {
                        "title": "",
                        "impact": "",
                        "description": ""
                      }
                    ],

                    "growth_zones": [
                      {
                        "title": "",
                        "priority": "",
                        "duration": "",
                        "description": ""
                      },
                      {
                        "title": "",
                        "priority": "",
                        "duration": "",
                        "description": ""
                      },
                      {
                        "title": "",
                        "priority": "",
                        "duration": "",
                        "description": ""
                      }
                    ],

                    "overall_evaluation": {
                      "title": "",
                      "comment": ""
                    },

                    "action_plan": {
                      "duration": "",
                      "focus": ""
                    }
                  },

                  "career_path": [
                    {
                      "position": "",
                      "company": "",
                      "period": "",
                      "experience": "",
                      "status": "",
                      "description": "",
                      "achievements": [
                        "",
                        "",
                        ""
                      ],
                      "tech_stack": []
                    },
                    {
                      "position": "",
                      "company": "",
                      "period": "",
                      "experience": "",
                      "status": "",
                      "description": "",
                      "achievements": [
                        "",
                        "",
                        ""
                      ],
                      "tech_stack": []
                    },
                    {
                      "position": "",
                      "company": "",
                      "period": "",
                      "experience": "",
                      "status": "",
                      "description": "",
                      "achievements": [
                        "",
                        "",
                        ""
                      ],
                      "tech_stack": []
                    }
                  ],

                  "career_path_summary": {
                    "experience_total": "",
                    "companies": "",
                    "growth": ""
                  },

                  "growth_roadmap_12_months": {
                    "months_1_3": {
                      "title": "",
                      "tasks": [],
                      "result": ""
                    },
                    "months_4_6": {
                      "title": "",
                      "tasks": [],
                      "result": ""
                    },
                    "months_7_9": {
                      "title": "",
                      "tasks": [],
                      "result": ""
                    },
                    "months_10_12": {
                      "title": "",
                      "tasks": [],
                      "result": ""
                    },
                    "forecast": {
                      "current_position": "",
                      "expected_after_12_months": "",
                      "probability": ""
                    }
                  },

                  "target_position": {
                    "title": "",
                    "alternative": "",
                    "salary": "",
                    "format": "",
                    "team_size": "",
                    "role_note": "",
                    "tech_stack": []
                  },

                  "gap_analysis": {
                    "skills": [
                      {
                        "name": "",
                        "current": "",
                        "target": "",
                        "gap": ""
                      },
                      {
                        "name": "",
                        "current": "",
                        "target": "",
                        "gap": ""
                      },
                      {
                        "name": "",
                        "current": "",
                        "target": "",
                        "gap": ""
                      },
                      {
                        "name": "",
                        "current": "",
                        "target": "",
                        "gap": ""
                      },
                      {
                        "name": "",
                        "current": "",
                        "target": "",
                        "gap": ""
                      },
                      {
                        "name": "",
                        "current": "",
                        "target": "",
                        "gap": ""
                      }
                    ],
                    "overall_readiness": {
                      "value": "",
                      "status": "",
                      "comment": ""
                    }
                  }
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
        ->timeout(300)
        ->post('https://api.openai.com/v1/chat/completions', [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful AI for analyzing resumes.'],
            ['role' => 'user', 'content' => $prompt],
          ],
        ]);

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
        Log::info('✅ Career PDF generated for resume ID: ' . $resume->id);
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
