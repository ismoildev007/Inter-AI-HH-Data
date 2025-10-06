<?php

namespace Modules\TelegramChannel\Services;

class VacancyCategoryService
{
    /**
     * Canonical category labels we support. Keep lowercase.
     */
    private array $canon = [
        // IT / Engineering (software)
        'developer', 'frontend_developer', 'backend_developer', 'fullstack_developer', 'mobile_developer',
        'devops', 'sysadmin', 'data', 'security', 'qa', 'product', 'project', 'analyst',
        // Design / Content / Media
        'designer', 'content', 'video_editor', 'motion_design', 'photographer', 'videographer',
        // Business / Marketing / Sales / Ops
        'marketer', 'smm', 'pr', 'communications', 'sales', 'business_development', 'customer_success', 'support',
        'hr', 'finance', 'accounting', 'banking', 'insurance', 'operations', 'procurement', 'supply_chain', 'warehouse', 'office_manager',
        // Hard engineering / Architecture / Construction
        'architect', 'civil_engineer', 'electrical_engineer', 'mechanical_engineer', 'automation_engineer', 'electronics_engineer', 'chemical_engineer',
        'construction',
        // Real estate / Legal
        'real_estate', 'legal',
        // Education / Translation
        'teacher', 'tutor', 'trainer', 'translator', 'interpreter',
        // Healthcare
        'medicine', 'nurse', 'pharmacist', 'dentist', 'veterinarian',
        // Hospitality / Retail
        'hospitality', 'chef', 'cook', 'baker', 'pastry_chef', 'bartender', 'waiter', 'retail', 'cashier',
        // Logistics / Driving / Technicians / Trades
        'driver', 'courier', 'logistics', 'technician', 'welder', 'electrician', 'plumber', 'mechanic', 'carpenter', 'painter', 'seamstress',
        // Tourism / Beauty
        'tourism', 'travel_agent', 'beauty',
        // Default
        'other',
    ];

    /**
     * Normalize a raw category string into one of the canonical labels.
     */
    public function normalize(string $raw): string
    {
        $s = trim(mb_strtolower($raw));
        if ($s === '') return 'other';

        // direct matches
        if (in_array($s, $this->canon, true)) return $s;

        // synonyms mapping
        $map = [
            // Developers
            'programmer' => 'developer', 'software engineer' => 'developer',
            'frontend' => 'frontend_developer', 'frontend developer' => 'frontend_developer', 'react' => 'frontend_developer', 'vue' => 'frontend_developer',
            'backend' => 'backend_developer', 'backend developer' => 'backend_developer', 'spring' => 'backend_developer', 'laravel' => 'backend_developer', 'symfony' => 'backend_developer',
            'fullstack' => 'fullstack_developer', 'full-stack' => 'fullstack_developer', 'full stack' => 'fullstack_developer',
            'mobile' => 'mobile_developer', 'android' => 'mobile_developer', 'ios' => 'mobile_developer', 'flutter' => 'mobile_developer', 'react native' => 'mobile_developer',
            // Data / Security / QA / DevOps / Sysadmin
            'data engineer' => 'data', 'data scientist' => 'data', 'ml' => 'data', 'ai' => 'data',
            'security engineer' => 'security', 'secops' => 'security', 'pentester' => 'security', 'soc' => 'security',
            'qa engineer' => 'qa', 'quality assurance' => 'qa', 'tester' => 'qa', 'automation qa' => 'qa', 'qa automation' => 'qa',
            'sre' => 'devops', 'devops engineer' => 'devops', 'platform engineer' => 'devops',
            'sysadmin' => 'sysadmin', 'system administrator' => 'sysadmin', 'network admin' => 'sysadmin',
            // Design / Content / Media
            'ui' => 'designer', 'ux' => 'designer', 'ui/ux' => 'designer', 'graphic' => 'designer', 'product designer' => 'designer',
            'content' => 'content', 'copywriter' => 'content', 'copywriting' => 'content', 'writer' => 'content', 'editor' => 'content',
            'video editor' => 'video_editor', 'montajchi' => 'video_editor', 'видеомонтаж' => 'video_editor',
            'motion designer' => 'motion_design', 'after effects' => 'motion_design',
            'photographer' => 'photographer', 'fotograf' => 'photographer',
            'videographer' => 'videographer',
            // Marketing / Sales / PR / Comms / BizDev / CS / Support
            'marketing' => 'marketer', 'marketolog' => 'marketer', 'digital marketing' => 'marketer', 'seo' => 'marketer', 'ppc' => 'marketer',
            'social media' => 'smm', 'smm manager' => 'smm', 'targetolog' => 'smm',
            'pr manager' => 'pr', 'public relations' => 'pr', 'pr' => 'pr',
            'communications' => 'communications', 'comms' => 'communications',
            'sales manager' => 'sales', 'sales representative' => 'sales', 'account manager' => 'sales', 'sotuvchi' => 'sales', 'sotuv' => 'sales',
            'bd manager' => 'business_development', 'business development' => 'business_development',
            'customer success' => 'customer_success', 'cs manager' => 'customer_success',
            'call center' => 'support', 'operator' => 'support', 'support' => 'support', 'helpdesk' => 'support',
            // HR / Finance / Accounting / Banking / Insurance / Ops
            'hr' => 'hr', 'recruiter' => 'hr', 'talent acquisition' => 'hr',
            'finance manager' => 'finance', 'finance' => 'finance',
            'accountant' => 'accounting', 'buxgalter' => 'accounting',
            'bank' => 'banking', 'banking' => 'banking',
            'insurance' => 'insurance', 'strahovanie' => 'insurance',
            'operations' => 'operations', 'ops manager' => 'operations', 'office manager' => 'office_manager',
            'procurement' => 'procurement', 'supply chain' => 'supply_chain', 'supply' => 'supply_chain', 'warehouse' => 'warehouse', 'sklad' => 'warehouse',
            // Engineering / Architecture / Construction
            'architect' => 'architect', 'architecture' => 'architect',
            'civil engineer' => 'civil_engineer', 'injinir qurilish' => 'civil_engineer', 'injinir' => 'civil_engineer',
            'electrical engineer' => 'electrical_engineer', 'elektr' => 'electrical_engineer',
            'mechanical engineer' => 'mechanical_engineer', 'mexanik injener' => 'mechanical_engineer',
            'automation engineer' => 'automation_engineer', 'mechatronics' => 'automation_engineer',
            'electronics engineer' => 'electronics_engineer', 'radio engineer' => 'electronics_engineer',
            'chemical engineer' => 'chemical_engineer',
            'quruvchi' => 'construction', 'construction' => 'construction', 'muhandis' => 'construction',
            // Real estate / Legal
            'broker' => 'real_estate', 'rieltor' => 'real_estate', 'realtor' => 'real_estate',
            'yurist' => 'legal', 'lawyer' => 'legal', 'legal' => 'legal',
            // Education / Translation
            'teacher' => 'teacher', 'o\'qituvchi' => 'teacher', 'oqituvchi' => 'teacher', 'ustoz' => 'teacher',
            'tutor' => 'tutor', 'repetitor' => 'tutor',
            'trainer' => 'trainer', 'coach' => 'trainer',
            'translator' => 'translator', 'perevodchik' => 'translator', 'tarjimon' => 'translator',
            'interpreter' => 'interpreter',
            // Healthcare
            'doctor' => 'medicine', 'shifokor' => 'medicine', 'medic' => 'medicine',
            'nurse' => 'nurse', 'hamshira' => 'nurse',
            'pharmacist' => 'pharmacist', 'dorixonachi' => 'pharmacist', 'apteka' => 'pharmacist',
            'dentist' => 'dentist', 'stomatolog' => 'dentist',
            'veterinarian' => 'veterinarian', 'veterinar' => 'veterinarian',
            // Hospitality / Retail
            'barista' => 'hospitality', 'hotel' => 'hospitality', 'receptionist' => 'hospitality',
            'chef' => 'chef', 'oshpaz' => 'cook', 'cook' => 'cook', 'povar' => 'cook',
            'baker' => 'baker', 'pastry chef' => 'pastry_chef',
            'bartender' => 'bartender', 'waiter' => 'waiter', 'ofitsiant' => 'waiter', 'официант' => 'waiter',
            'retail' => 'retail', 'cashier' => 'cashier', 'kassir' => 'cashier',
            // Logistics / Driving / Technicians / Trades
            'driver' => 'driver', 'haydovchi' => 'driver', 'kur\'yer' => 'courier', 'courier' => 'courier', 'delivery' => 'courier',
            'logistika' => 'logistics', 'logistics' => 'logistics',
            'technician' => 'technician', 'montajchi' => 'technician', 'installer' => 'technician',
            'welder' => 'welder', 'svarka' => 'welder', 'svarshchik' => 'welder', 'payvandchi' => 'welder',
            'electrician' => 'electrician', 'elektrik' => 'electrician',
            'plumber' => 'plumber', 'santexnik' => 'plumber',
            'mechanic' => 'mechanic', 'avtosoz' => 'mechanic',
            'carpenter' => 'carpenter', 'duradgor' => 'carpenter',
            'painter' => 'painter', 'bo\'yovchi' => 'painter', 'malyr' => 'painter',
            'seamstress' => 'seamstress', 'tikuvchi' => 'seamstress',
            // Tourism / Beauty
            'tourism' => 'tourism', 'travel agent' => 'travel_agent', 'turizm' => 'tourism', 'gid' => 'travel_agent',
            'sartarosh' => 'beauty', 'barber' => 'beauty', 'go\'zallik' => 'beauty', 'kosmetolog' => 'beauty', 'vizajist' => 'beauty',
        ];
        if (isset($map[$s])) return $map[$s];

        return 'other';
    }

    /**
     * Infer category from title/description keywords if AI didn't provide a good one.
     */
    public function inferFromText(string $title, string $description = ''): string
    {
        $t = mb_strtolower($title . ' ' . $description);

        $rules = [
            // IT / software
            'frontend_developer' => ['frontend', 'react', 'vue', 'nuxt', 'next.js', 'angular', 'typescript', 'tailwind', 'sass'],
            'backend_developer'  => ['backend', 'laravel', 'symfony', 'django', 'flask', 'spring', 'asp.net', 'node.js', 'express'],
            'fullstack_developer'=> ['fullstack', 'full-stack', 'mern', 'mevn', 'lamp', 'lemp'],
            'mobile_developer'   => ['android', 'ios', 'flutter', 'react native', 'kotlin', 'swift', 'xcode'],
            'developer'          => ['php', 'python', 'java', 'golang', 'rust', 'c#', '.net', 'c++', 'programmer', 'software engineer'],
            'devops'             => ['devops', 'sre', 'gitlab ci', 'github actions', 'kubernetes', 'k8s', 'docker', 'terraform', 'ansible', 'helm', 'prometheus', 'grafana'],
            'sysadmin'           => ['sysadmin', 'system administrator', 'linux', 'windows server', 'active directory', 'network admin', 'mikrotik', 'cisco'],
            'data'               => ['data engineer', 'data scientist', 'ml', 'machine learning', 'etl', 'airflow', 'spark', 'pandas', 'numpy'],
            'security'           => ['security', 'appsec', 'pentest', 'siem', 'soc', 'blue team', 'red team'],
            'qa'                 => ['qa', 'qa engineer', 'tester', 'testing', 'automation', 'selenium', 'cypress', 'postman', 'jmeter'],
            'product'            => ['product manager', 'product owner', 'po', 'roadmap', 'backlog'],
            'project'            => ['project manager', 'scrum master', 'pm', 'jira', 'kanban'],
            'analyst'            => ['analyst', 'analytics', 'business analyst', 'sql', 'bi', 'power bi', 'tableau'],
            // Design / Content / Media
            'designer'           => ['ui', 'ux', 'ui/ux', 'figma', 'sketch', 'adobe xd', 'wireframe', 'prototype'],
            'content'            => ['content', 'copywriter', 'copywriting', 'writer', 'editor', 'smm content'],
            'video_editor'       => ['video editor', 'premiere', 'after effects', 'montaj', 'видеомонтаж'],
            'motion_design'      => ['motion', 'after effects', 'motion designer'],
            'photographer'       => ['photographer', 'photo', 'fotograf'],
            'videographer'       => ['videographer', 'video shoot', 'operator'],
            // Marketing / Sales / PR / Comms / BizDev / CS / Support
            'marketer'           => ['marketing', 'marketolog', 'seo', 'ppc', 'sem', 'context', 'digital marketing'],
            'smm'                => ['smm', 'targetolog', 'social media', 'instagram', 'facebook ads', 'tiktok'],
            'pr'                 => ['pr', 'public relations', 'press'],
            'communications'     => ['communications', 'comms', 'internal comms'],
            'sales'              => ['sales', 'sotuvchi', 'account manager', 'sales manager', 'b2b', 'b2c', 'cold calling'],
            'business_development'=>['bd', 'business development', 'partnerships'],
            'customer_success'   => ['customer success', 'onboarding', 'retention'],
            'support'            => ['call center', 'operator', 'support', 'helpdesk', 'service desk'],
            // HR / Finance / Accounting / Banking / Insurance / Ops
            'hr'                 => ['hr', 'recruiter', 'talent acquisition', 'kadrlar'],
            'finance'            => ['finance', 'finans', 'financial'],
            'accounting'         => ['accountant', 'accounting', 'buxgalter', 'buxgalteriya'],
            'banking'            => ['bank', 'banking', 'filial', 'kredit'],
            'insurance'          => ['insurance', 'strahovanie', 'policy'],
            'operations'         => ['operations', 'ops manager', 'process'],
            'procurement'        => ['procurement', 'zakup', 'sotib olish'],
            'supply_chain'       => ['supply chain', 'supply', 'logistika zanjiri'],
            'warehouse'          => ['warehouse', 'sklad', 'kladovshchik'],
            'office_manager'     => ['office manager', 'administrator office'],
            // Engineering / Architecture / Construction
            'architect'          => ['architect', 'architecture'],
            'civil_engineer'     => ['civil engineer', 'qurilish injener', 'injinir qurilish'],
            'electrical_engineer' => ['electrical engineer', 'elektr injener'],
            'mechanical_engineer' => ['mechanical engineer', 'mexanik injener'],
            'automation_engineer' => ['automation engineer', 'mechatronics', 'plc'],
            'electronics_engineer' => ['electronics engineer', 'radio engineer'],
            'chemical_engineer'  => ['chemical engineer', 'kimyo injener'],
            'construction'       => ['quruvchi', 'construction', 'muhandis', 'injener', 'usta'],
            // Real estate / Legal
            'real_estate'        => ['real estate', 'broker', 'rieltor', 'realtor', 'nedvijimost'],
            'legal'              => ['lawyer', 'yurist', 'legal', 'contract'],
            // Education / Translation
            'teacher'            => ['teacher', 'o\'qituvchi', 'oqituvchi', 'ustoz', 'dars'],
            'tutor'              => ['tutor', 'repetitor'],
            'trainer'            => ['trainer', 'coach', 'trening'],
            'translator'         => ['translator', 'perevodchik', 'tarjimon'],
            'interpreter'        => ['interpreter', 'sinxron perevod'],
            // Healthcare
            'medicine'           => ['doctor', 'shifokor', 'medic'],
            'nurse'              => ['nurse', 'hamshira'],
            'pharmacist'         => ['pharmacist', 'dorixonachi', 'apteka'],
            'dentist'            => ['dentist', 'stomatolog'],
            'veterinarian'       => ['veterinarian', 'veterinar'],
            // Hospitality / Retail
            'hospitality'        => ['barista', 'hotel', 'receptionist', 'hostess'],
            'chef'               => ['chef', 'bosh oshpaz'],
            'cook'               => ['cook', 'oshpaz', 'povar'],
            'baker'              => ['baker', 'nonvoy'],
            'pastry_chef'        => ['pastry chef', 'konditer'],
            'bartender'          => ['bartender', 'barmen'],
            'waiter'             => ['waiter', 'ofitsiant', 'официант'],
            'retail'             => ['retail', 'do\'kon', 'magazin'],
            'cashier'            => ['cashier', 'kassir'],
            // Logistics / Driving / Technicians / Trades
            'driver'             => ['driver', 'haydovchi'],
            'courier'            => ['courier', 'delivery', 'kur\'yer'],
            'logistics'          => ['logistics', 'logistika'],
            'technician'         => ['technician', 'montajchi', 'installer', 'ustaxonachi'],
            'welder'             => ['welder', 'svarka', 'svarshchik', 'payvandchi'],
            'electrician'        => ['electrician', 'elektrik'],
            'plumber'            => ['plumber', 'santexnik'],
            'mechanic'           => ['mechanic', 'avtosoz'],
            'carpenter'          => ['carpenter', 'duradgor'],
            'painter'            => ['painter', 'bo\'yovchi', 'malyr'],
            'seamstress'         => ['seamstress', 'tikuvchi'],
            // Tourism / Beauty
            'tourism'            => ['tourism', 'turizm'],
            'travel_agent'       => ['travel agent', 'gid', 'tur agent'],
            'beauty'             => ['sartarosh', 'barber', 'go\'zallik', 'kosmetolog', 'vizajist'],
        ];

        foreach ($rules as $cat => $needles) {
            foreach ($needles as $n) {
                if (str_contains($t, $n)) return $cat;
            }
        }

        return 'other';
    }

    /**
     * High-level: combine raw AI category + heuristic fallback into a single canonical label.
     */
    public function categorize(?string $raw, string $title, string $description = ''): string
    {
        $cand = $this->normalize((string) $raw);
        if ($cand !== 'other' && $cand !== '') {
            return $cand;
        }
        return $this->inferFromText($title, $description);
    }
}
