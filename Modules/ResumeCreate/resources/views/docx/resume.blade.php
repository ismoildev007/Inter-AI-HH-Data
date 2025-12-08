{{-- Simple DOCX-friendly version of the resume layout --}}
<h1>{{ $resume->first_name }} {{ $resume->last_name }}</h1>
@if($resume->desired_position)
    <p><strong>{{ $resume->desired_position }}</strong></p>
@endif
<p>
    @if($resume->phone)
        {{ $resume->phone }}
    @endif
    @if($resume->phone && $resume->contact_email)
        &nbsp;|&nbsp;
    @endif
    @if($resume->contact_email)
        {{ $resume->contact_email }}
    @endif
</p>
@if($resume->city || $resume->country)
    <p>
        {{ $resume->city }}@if($resume->city && $resume->country), @endif{{ $resume->country }}
    </p>
@endif
@if($resume->linkedin_url)
    <p>LinkedIn: {{ $resume->linkedin_url }}</p>
@endif
@if($resume->github_url)
    <p>GitHub: {{ $resume->github_url }}</p>
@endif
@if($resume->portfolio_url)
    <p>Portfolio: {{ $resume->portfolio_url }}</p>
@endif

<h2>{{ $labels['section_professional_summary'] ?? 'PROFESSIONAL SUMMARY' }}</h2>
<p>{{ $t['professional_summary'] ?? $resume->professional_summary }}</p>

@if($resume->experiences->isNotEmpty())
    <h2>{{ $labels['section_work_experience'] ?? 'WORK EXPERIENCE' }}</h2>
    @foreach($resume->experiences as $index => $exp)
        @php
            $txExpList = $t['experiences'] ?? [];
            $txExp = is_array($txExpList) && array_key_exists($index, $txExpList) ? $txExpList[$index] : null;
        @endphp
        <p>
            <strong>{{ $exp->position }}</strong><br>
            @if($exp->start_date)
                {{ $exp->start_date->format('M Y') }}
            @endif
            -
            @if($exp->is_current)
                {{ $labels['present'] ?? 'Present' }}
            @elseif($exp->end_date)
                {{ $exp->end_date->format('M Y') }}
            @endif
            <br>
            {{ $txExp['company'] ?? $exp->company }}@if(($txExp['company'] ?? $exp->company) && ($txExp['location'] ?? $exp->location)), @endif
            {{ $txExp['location'] ?? $exp->location }}<br>
            {{ $txExp['description'] ?? $exp->description }}
        </p>
    @endforeach
@endif

@if($resume->educations->isNotEmpty())
    <h2>{{ $labels['section_education'] ?? 'EDUCATION' }}</h2>
    @foreach($resume->educations as $index => $edu)
        @php
            $txEduList = $t['educations'] ?? [];
            $txEdu = is_array($txEduList) && array_key_exists($index, $txEduList) ? $txEduList[$index] : null;
        @endphp
        <p>
            <strong>{{ $edu->degree }}</strong><br>
            @if($edu->start_date)
                {{ $edu->start_date->format('Y') }}
            @endif
            <br>
            {{ $txEdu['institution'] ?? $edu->institution }}@if(($txEdu['institution'] ?? $edu->institution) && ($txEdu['location'] ?? $edu->location)), @endif
            {{ $txEdu['location'] ?? $edu->location }}<br>
            {{ $txEdu['extra_info'] ?? $edu->extra_info }}
        </p>
    @endforeach
@endif

@if($resume->skills->isNotEmpty())
    <h2>{{ $labels['section_skills'] ?? 'SKILLS' }}</h2>
    @php
        $txSkills = $t['skills'] ?? [];
        $groupedSkills = [];
        foreach ($resume->skills as $index => $skill) {
            $txSkill = (is_array($txSkills) && array_key_exists($index, $txSkills)) ? $txSkills[$index] : null;
            $levelLabel = $txSkill['level'] ?? $skill->level;
            $name = $skill->name;
            if (! $levelLabel || ! $name) {
                continue;
            }
            if (! array_key_exists($levelLabel, $groupedSkills)) {
                $groupedSkills[$levelLabel] = [];
            }
            $groupedSkills[$levelLabel][] = $name;
        }
    @endphp
    @foreach($groupedSkills as $level => $names)
        <p><strong>{{ $level }}:</strong> {{ implode(', ', $names) }}</p>
    @endforeach
@endif

@php
    $employmentItems = (array) ($resume->employment_types ?? []);
    $scheduleItems = (array) ($resume->work_schedules ?? []);

    $employmentMap = [
        'full_time' => ['ru' => 'Полная занятость', 'en' => 'Full time'],
        'part_time' => ['ru' => 'Частичная занятость', 'en' => 'Part time'],
        'project' => ['ru' => 'Проектная работа', 'en' => 'Project work'],
        'internship' => ['ru' => 'Стажировка', 'en' => 'Internship'],
        'volunteer' => ['ru' => 'Волонтёрство', 'en' => 'Volunteering'],
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
            'employment' => 'Employment',
            'schedule' => 'Work schedule',
        ];

    $readinessLabels = $lang === 'ru'
        ? ['relocate' => 'Готов к переезду', 'trips' => 'Готов к командировкам']
        : ['relocate' => 'Willing to relocate', 'trips' => 'Willing to travel'];
@endphp

@if($resume->desired_salary || $resume->citizenship || $employmentText || $scheduleText || $resume->ready_to_relocate || $resume->ready_for_trips)
    <h2>{{ $labels['section_preferences'] ?? 'JOB PREFERENCES' }}</h2>
    <p>
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
    </p>
@endif

@if(collect($resume->languages ?? [])->isNotEmpty())
    <h2>{{ $labels['section_languages'] ?? 'LANGUAGES' }}</h2>
    @php
        $txLangs = $t['languages'] ?? [];
        $langParts = [];
        foreach ($resume->languages as $index => $langItem) {
            $txLang = is_array($txLangs) && array_key_exists($index, $txLangs) ? $txLangs[$index] : null;
            $name = $langItem['name'] ?? '';
            $level = $txLang['level'] ?? ($langItem['level'] ?? '');
            if (! $name || ! $level) {
                continue;
            }
            $langParts[] = $name.': '.$level;
        }
    @endphp
    <p>{{ implode(', ', $langParts) }}</p>
@endif

@if(collect($resume->certificates ?? [])->isNotEmpty())
    <h2>{{ $labels['section_certifications'] ?? 'CERTIFICATIONS' }}</h2>
    @php $txCerts = $t['certificates'] ?? []; @endphp
    @foreach($resume->certificates as $index => $cert)
        @php
            $txCert = is_array($txCerts) && array_key_exists($index, $txCerts) ? $txCerts[$index] : null;
            $title = $txCert['title'] ?? ($cert['title'] ?? '');
            $org = $txCert['organization'] ?? ($cert['organization'] ?? '');
            $issued = $cert['issued_at'] ?? '';
        @endphp
        <p>
            ✔ {{ $title }}
            @if($org || $issued)
                — {{ $org }}@if($issued), {{ $issued }}@endif
            @endif
        </p>
    @endforeach
@endif

