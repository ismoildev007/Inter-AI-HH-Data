<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 32px 40px;
            position: relative;
            background-color: #ffffff;
        }
        .top-logo {
            position: absolute;
            top: -40px;
            right: -30px;
        }
        .top-logo img {
            height: 70px;
        }
        .bg-izotip {
            position: absolute;
            top: -150px;
            right: -160px; /* rasmning taxminan 30% qismi tashqarida qoladi */
            opacity: 0.35; /* biroz ko'rinarliroq qilamiz */
        }
        .bg-izotip img {
            width: 530px;
            height: auto;
        }
        .header {
            margin-bottom: 24px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-photo-cell {
            width: 96px;
            vertical-align: top;
            padding-right: 24px;
        }
        .header-main-cell {
            vertical-align: top;
        }
        .photo {
            width: 96px;
            height: 96px;
            border-radius: 8px;
            overflow: hidden;
            background: #e5e7eb;
            margin-right: 24px;
            border: 1px solid #e5e7eb;
        }
        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* kvadrat avatar, markazdan crop */
            display: block;
        }
        .header-main {
        }
        .name {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .position {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 8px;
        }
        .contacts {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.4;
        }
        .contact-line {
            margin: 2px 0;
        }
        .contact-icon-img {
            display: inline-block;
            vertical-align: -2px;
            margin-right: 4px;
        }
        hr {
            border: none;
            border-top: 2px solid #000000; /* qalin chiziq qora */
            margin: 16px 0;
        }
        .section {
            margin-bottom: 16px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.05em;
            color: #111827;
        }
        /* Faqat 2-bo'limdan boshlab chiziq chizish */
        .section + .section .section-header {
            margin-top: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #9ca3af; /* gray-400 */
        }
        .section-title-secondary {
            font-weight: 400;
            color: #6b7280;
        }
        .section-header .experience-total {
            font-weight: normal;
            color: #6b7280;
            margin-left: 4px;
        }
        .section-body {
            font-size: 11px;
            color: #111827;
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            margin-top: 10px; /* sarlavhadan yanada pastroqda bo'lsin */
        }
        .section-meta {
            margin-bottom: 8px;
        }
        .experience-item,
        .education-item,
        .skill-group,
        .certificate-item {
            margin-bottom: 8px;
        }
        .experience-header,
        .education-header,
        .certificate-header {
            font-weight: bold;
            font-size: 11px;
            color: #374151; /* slightly softer than pure black */
        }

        .experience-header,
        .education-header {
            display: table;
            width: 100%;
        }

        .experience-header > div:first-child,
        .education-header > div:first-child {
            display: table-cell;
        }

        .experience-header > div:last-child,
        .education-header > div:last-child {
            display: table-cell;
            text-align: right;
            white-space: nowrap;
        }
        .muted {
            color: #6b7280;
        }
        /* Bold label matnlar uchun ham shu rang */
        .section-body strong,
        .section-meta strong {
            color: #374151;
        }
        .badge {
            display: inline-block;
            padding: 0 4px;
            font-size: 10px;
            margin-right: 4px;
            margin-bottom: 2px;
        }
        .check {
            color: #16a34a;
            font-weight: 700;
            margin-right: 4px;
        }
        .skill-tags {
            font-size: 11px;
        }
        .skill-tag {
            display: inline-block;
            padding: 2px 6px; /* yozuv atrofida ixcham border, buttonga o'xshash */
            margin: 2px 3px 2px 0;
            border-radius: 4px;
            background-color: #e5e7eb; /* gray-200 */
            border: 1px solid #d1d5db; /* gray-300 */
            color: #111827;
            position: relative;
            top: 0px; /* matnni biroz tepaga ko'taramiz */
        }
    </style>
</head>
<body>
    @php $izotip = public_path('pdf-icons/izotip.png'); @endphp
    @if(file_exists($izotip))
        <div class="bg-izotip">
            <img src="{{ $izotip }}" alt="izotip background">
        </div>
    @endif
    <div class="top-logo">
        @php $companyLogo = public_path('pdf-icons/logo.svg'); @endphp
        @if(file_exists($companyLogo))
            <img src="{{ $companyLogo }}" alt="logo">
        @endif
    </div>

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-photo-cell">
                    <div class="photo">
                        @if($photo_base64)
                            <img src="{{ $photo_base64 }}" alt="photo">
                        @endif
                    </div>
                </td>
                <td class="header-main-cell">
                    <div class="header-main">
                        <div class="name">
                            {{ $resume->first_name }} {{ $resume->last_name }}
                        </div>
                        <div class="position">
                            {{ $resume->desired_position }}
                        </div>
                        @php
                            $linkLabels = $lang === 'ru'
                                ? ['linkedin' => 'LinkedIn:', 'github' => 'GitHub:', 'portfolio' => 'Портфолио:']
                                : ['linkedin' => 'LinkedIn:', 'github' => 'GitHub:', 'portfolio' => 'Portfolio:'];
                        @endphp
                        <div class="contacts">
                            @if($resume->phone || $resume->contact_email)
                                <div class="contact-line">
                                    @if($resume->phone)
                                        @php $phoneIcon = public_path('pdf-icons/phone.png'); @endphp
                                        @if(file_exists($phoneIcon))
                                            <img src="{{ $phoneIcon }}" alt="phone" width="10" height="10" class="contact-icon-img">
                                        @endif
                                        {{ $resume->phone }}
                                    @endif
                                    @if($resume->contact_email)
                                        &nbsp;|&nbsp;
                                        @php $mailIcon = public_path('pdf-icons/email.png'); @endphp
                                        @if(file_exists($mailIcon))
                                            <img src="{{ $mailIcon }}" alt="email" width="10" height="10" class="contact-icon-img">
                                        @endif
                                        {{ $resume->contact_email }}
                                    @endif
                                </div>
                            @endif

                            @if($resume->city || $resume->country)
                                <div class="contact-line">
                                    @php $locIcon = public_path('pdf-icons/location.png'); @endphp
                                    @if(file_exists($locIcon))
                                        <img src="{{ $locIcon }}" alt="location" width="10" height="10" class="contact-icon-img">
                                    @endif
                                    {{ $resume->city }}@if($resume->city && $resume->country), @endif{{ $resume->country }}
                                </div>
                            @endif

                            @if($resume->linkedin_url)
                                <div class="contact-line">
                                    @php $liIcon = public_path('pdf-icons/linkedin.png'); @endphp
                                    @if(file_exists($liIcon))
                                        <img src="{{ $liIcon }}" alt="linkedin" width="10" height="10" class="contact-icon-img">
                                    @endif
                                    {{ $resume->linkedin_url }}
                                </div>
                            @endif

                            @if($resume->github_url)
                                <div class="contact-line">
                                    @php $ghIcon = public_path('pdf-icons/github.png'); @endphp
                                    @if(file_exists($ghIcon))
                                        <img src="{{ $ghIcon }}" alt="github" width="10" height="10" class="contact-icon-img">
                                    @endif
                                    {{ $resume->github_url }}
                                </div>
                            @endif

                            @if($resume->portfolio_url)
                                <div class="contact-line">
                                    @php $webIcon = public_path('pdf-icons/web.png'); @endphp
                                    @if(file_exists($webIcon))
                                        <img src="{{ $webIcon }}" alt="web" width="10" height="10" class="contact-icon-img">
                                    @endif
                                    {{ $resume->portfolio_url }}
                                </div>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <hr>

    @php
        $summaryText = trim((string) ($t['professional_summary'] ?? $resume->professional_summary));
    @endphp
    @if($summaryText !== '')
        <div class="section">
            <div class="section-header">
                <div>{{ $labels['section_professional_summary'] ?? 'PROFESSIONAL SUMMARY' }}</div>
            </div>
            <div class="section-body">
                {{ $summaryText }}
            </div>
        </div>
    @endif

    @if($resume->experiences->isNotEmpty())
        <div class="section">
            <div class="section-header">
                @php
                    $totalMonths = 0;
                    foreach ($resume->experiences as $exp) {
                        if ($exp->start_date) {
                            $start = $exp->start_date;
                            $end = $exp->is_current
                                ? \Carbon\Carbon::now()
                                : ($exp->end_date ?: $exp->start_date);
                            $totalMonths += $start->diffInMonths($end);
                        }
                    }
                    $totalYears = $totalMonths > 0 ? intdiv($totalMonths, 12) : 0;
                @endphp
                <div>
                    {{ $labels['section_work_experience'] ?? "ISH TAJRIBASI / WORK EXPERIENCE" }}
                    @if($totalMonths > 0)
                        <span class="experience-total">
                            -
                            @if($totalMonths < 12)
                                {{ $lang === 'ru' ? 'менее 1 года' : 'less than 1 year' }}
                            @else
                                {{ $totalYears }} {{ $lang === 'ru' ? 'лет' : ($totalYears === 1 ? 'year' : 'years') }}
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div class="section-body">
                @foreach($resume->experiences as $index => $exp)
                    @php
                        $txExpList = $t['experiences'] ?? [];
                        $txExp = is_array($txExpList) && array_key_exists($index, $txExpList) ? $txExpList[$index] : null;
                    @endphp
                    <div class="experience-item">
                        <div class="experience-header">
                            <div>{{ $exp->position }}</div>
                            <div class="muted">
                                @if($exp->start_date)
                                    {{ $exp->start_date->format('M Y') }}
                                @endif
                                -
                                @if($exp->is_current)
                                    {{ $labels['present'] ?? 'Present' }}
                                @elseif($exp->end_date)
                                    {{ $exp->end_date->format('M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="muted">
                            {{ $txExp['company'] ?? $exp->company }}@if(($txExp['company'] ?? $exp->company) && ($txExp['location'] ?? $exp->location)) | @endif{{ $txExp['location'] ?? $exp->location }}
                        </div>
                        @php
                            $desc = $txExp['description'] ?? $exp->description;
                        @endphp
                        @if($desc)
                            <div>
                                {{ $desc }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($resume->educations->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <div>{{ $labels['section_education'] ?? "TA'LIM / EDUCATION" }}</div>
            </div>
            <div class="section-body">
                @foreach($resume->educations as $index => $edu)
                    @php
                        $txEduList = $t['educations'] ?? [];
                        $txEdu = is_array($txEduList) && array_key_exists($index, $txEduList) ? $txEduList[$index] : null;
                    @endphp
                    <div class="education-item">
                        <div class="education-header">
                            <div>{{ $edu->degree }}</div>
                            <div class="muted">
                                @if($edu->start_date)
                                    {{ $edu->start_date->format('Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="muted">
                            {{ $txEdu['institution'] ?? $edu->institution }}@if(($txEdu['institution'] ?? $edu->institution) && ($txEdu['location'] ?? $edu->location)) | @endif{{ $txEdu['location'] ?? $edu->location }}
                        </div>
                        @php
                            $extra = $txEdu['extra_info'] ?? $edu->extra_info;
                        @endphp
                        @if($extra)
                            <div>{{ $extra }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($resume->skills->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <div>{{ $labels['section_skills'] ?? "KO'NIKMALAR / SKILLS" }}</div>
            </div>
            <div class="section-body">
                @php
                    $txSkills = $t['skills'] ?? [];
                @endphp

                <div class="skill-tags">
                    @foreach($resume->skills as $index => $skill)
                        @php
                            $txSkill = (is_array($txSkills) && array_key_exists($index, $txSkills)) ? $txSkills[$index] : null;
                            $name = $skill->name ?? '';
                        @endphp
                        @if(trim((string) $name) !== '')
                            <span class="skill-tag">{{ $name }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @php
        $prefLabels = $lang === 'ru'
            ? [
                'salary' => 'Желаемая зарплата',
                'citizenship' => 'Гражданство',
                'employment' => 'Занятость',
                'schedule' => 'График работы',
            ]
            : [
                'salary' => 'Desired salary',
                'citizenship' => 'Citizenship',
                'employment' => 'Employment type',
                'schedule' => 'Work schedule',
            ];

        $employmentItems = is_array($resume->employment_types) ? $resume->employment_types : (array) ($resume->employment_types ? [$resume->employment_types] : []);
        $scheduleItems = is_array($resume->work_schedules) ? $resume->work_schedules : (array) ($resume->work_schedules ? [$resume->work_schedules] : []);

        $employmentMap = [
            'full_time' => ['ru' => 'Полная занятость', 'en' => 'Full-time'],
            'part_time' => ['ru' => 'Частичная занятость', 'en' => 'Part-time'],
            'project' => ['ru' => 'Проектная работа', 'en' => 'Project work'],
            'internship' => ['ru' => 'Стажировка', 'en' => 'Internship'],
            'volunteer' => ['ru' => 'Волонтёрство', 'en' => 'Volunteer'],
        ];

        $scheduleMap = [
            'full_day' => ['ru' => 'Полный день', 'en' => 'Full day'],
            'shift' => ['ru' => 'Сменный график', 'en' => 'Shift schedule'],
            'flex' => ['ru' => 'Гибкий график', 'en' => 'Flexible schedule'],
            'remote' => ['ru' => 'Удалённая работа', 'en' => 'Remote work'],
            'rotation' => ['ru' => 'Вахтовый метод', 'en' => 'Rotation'],
        ];

        $locale = $lang === 'ru' ? 'ru' : 'en';

        $mapLabel = function (string $value, array $map, string $locale): string {
            $key = strtolower($value);
            if (isset($map[$key][$locale])) {
                return $map[$key][$locale];
            }

            $value = str_replace('_', ' ', $value);
            return ucfirst($value);
        };

        $employmentText = implode(', ', array_map(
            fn($v) => $mapLabel((string) $v, $employmentMap, $locale),
            $employmentItems
        ));

        $scheduleText = implode(', ', array_map(
            fn($v) => $mapLabel((string) $v, $scheduleMap, $locale),
            $scheduleItems
        ));

        $readinessLabels = $lang === 'ru'
            ? ['relocate' => 'Готов к переезду', 'trips' => 'Готов к командировкам']
            : ['relocate' => 'Willing to relocate', 'trips' => 'Willing to travel'];
    @endphp

    @if($resume->desired_salary || $resume->citizenship || $employmentText || $scheduleText || $resume->ready_to_relocate || $resume->ready_for_trips)
        <div class="section">
            <div class="section-header">
                <div>{{ $labels['section_preferences'] ?? 'JOB PREFERENCES' }}</div>
            </div>
            <div class="section-body">
                <div class="section-meta">
                    @if($resume->desired_salary)
                        <strong>{{ $prefLabels['salary'] }}:</strong> {{ $resume->desired_salary }}<br>
                    @endif
                    @if($resume->citizenship)
                        <strong>{{ $prefLabels['citizenship'] }}:</strong> {{ $resume->citizenship }}<br>
                    @endif
                    @if($employmentText)
                        <strong>{{ $prefLabels['employment'] }}:</strong> {{ $employmentText }}<br>
                    @endif
                    @if($scheduleText)
                        <strong>{{ $prefLabels['schedule'] }}:</strong> {{ $scheduleText }}<br>
                    @endif

                    @if($resume->ready_to_relocate)
                        <strong>{{ $readinessLabels['relocate'] }}</strong><br>
                    @endif
                    @if($resume->ready_for_trips)
                        <strong>{{ $readinessLabels['trips'] }}</strong>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @php
        $rawLanguages = collect($resume->languages ?? []);
        $nonEmptyLanguages = $rawLanguages->filter(function ($item) {
            $name = $item['name'] ?? '';
            $level = $item['level'] ?? '';
            return trim((string) $name) !== '' && trim((string) $level) !== '';
        });
    @endphp
    @if($nonEmptyLanguages->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <div>{{ $labels['section_languages'] ?? 'TILLAR / LANGUAGES' }}</div>
            </div>
            <div class="section-body">
                @php
                    $txLangs = $t['languages'] ?? [];
                    foreach ($nonEmptyLanguages as $index => $langItem) {
                        $txLang = is_array($txLangs) && array_key_exists($index, $txLangs) ? $txLangs[$index] : null;
                        $name = $langItem['name'] ?? '';
                        $level = $txLang['level'] ?? ($langItem['level'] ?? '');

                        if (! $name || ! $level) {
                            continue;
                        }
                        echo '<div><strong>'.e($name).'</strong> — '.e($level).'</div>';
                    }
                @endphp
            </div>
        </div>
    @endif

    @if(collect($resume->certificates ?? [])->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <div>{{ $labels['section_certifications'] ?? 'SERTIFIKATLAR / CERTIFICATIONS' }}</div>
            </div>
            <div class="section-body">
                @foreach($resume->certificates as $index => $cert)
                    @php
                        $txCerts = $t['certificates'] ?? [];
                        $txCert = is_array($txCerts) && array_key_exists($index, $txCerts) ? $txCerts[$index] : null;
                    @endphp
                    <div class="certificate-item">
                        <span class="check">✔</span>
                        <span>{{ $txCert['title'] ?? ($cert['title'] ?? '') }}</span>
                        @php
                            $org = $txCert['organization'] ?? ($cert['organization'] ?? '');
                            $issued = $cert['issued_at'] ?? '';
                        @endphp
                        @if(!empty($org) || !empty($issued))
                            <span class="muted">
                                — {{ $org }}
                                @if(!empty($issued)),
                                    {{ $issued }}
                                @endif
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</body>
</html>
