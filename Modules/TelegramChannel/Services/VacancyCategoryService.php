<?php

namespace Modules\TelegramChannel\Services;

use Illuminate\Support\Str;

class VacancyCategoryService
{
    /**
     * Canonical categories (slug => public label).
     */
    private array $categories = [
        'marketing_and_advertising' => 'Marketing and Advertising',
        'sales_and_customer_relations' => 'Sales and Customer Relations',
        'it_and_software_development' => 'IT and Software Development',
        'data_science_and_analytics' => 'Data Science and Analytics',
        'product_and_project_management' => 'Product and Project Management',
        'qa_and_testing' => 'QA and Testing',
        'devops_and_cloud_engineering' => 'DevOps and Cloud Engineering',
        'cybersecurity' => 'Cybersecurity',
        'ui_ux_and_product_design' => 'UI/UX and Product Design',
        'content_and_copywriting' => 'Content and Copywriting',
        'video_and_multimedia_production' => 'Video and Multimedia Production',
        'photography' => 'Photography',
        'human_resources_and_recruitment' => 'Human Resources and Recruitment',
        'finance_and_accounting' => 'Finance and Accounting',
        'banking_and_insurance' => 'Banking and Insurance',
        'legal_and_compliance' => 'Legal and Compliance',
        'administration_and_office_support' => 'Administration and Office Support',
        'education_and_training' => 'Education and Training',
        'healthcare_and_medicine' => 'Healthcare and Medicine',
        'pharmacy' => 'Pharmacy',
        'dentistry' => 'Dentistry',
        'veterinary_care' => 'Veterinary Care',
        'manufacturing_and_industrial_engineering' => 'Manufacturing and Industrial Engineering',
        'mechanical_and_maintenance_engineering' => 'Mechanical and Maintenance Engineering',
        'electrical_and_electronics_engineering' => 'Electrical and Electronics Engineering',
        'construction_and_architecture' => 'Construction and Architecture',
        'logistics_and_supply_chain' => 'Logistics and Supply Chain',
        'warehouse_and_procurement' => 'Warehouse and Procurement',
        'transportation_and_driving' => 'Transportation and Driving',
        'customer_support_and_call_center' => 'Customer Support and Call Center',
        'hospitality_and_tourism' => 'Hospitality and Tourism',
        'food_and_beverage_service' => 'Food and Beverage Service',
        'retail_and_ecommerce' => 'Retail and E-commerce',
        'real_estate' => 'Real Estate',
        'beauty_and_personal_care' => 'Beauty and Personal Care',
        'sports_and_fitness' => 'Sports and Fitness',
        'agriculture_and_farming' => 'Agriculture and Farming',
        'other' => 'Other',
    ];

    /**
     * Alias mapping (sanitized key => canonical slug).
     */
    private array $aliasMap = [
        // Marketing & Advertising
        'marketing' => 'marketing_and_advertising',
        'маркетинг' => 'marketing_and_advertising',
        'marketolog' => 'marketing_and_advertising',
        'маркетолог' => 'marketing_and_advertising',
        'reklama' => 'marketing_and_advertising',
        'реклама' => 'marketing_and_advertising',
        'targetolog' => 'marketing_and_advertising',
        'таргетолог' => 'marketing_and_advertising',
        'digital marketing' => 'marketing_and_advertising',
        'brand manager' => 'marketing_and_advertising',
        'brand marketing' => 'marketing_and_advertising',
        'smm' => 'marketing_and_advertising',
        'pr' => 'marketing_and_advertising',
        'public relations' => 'marketing_and_advertising',
        'communications specialist' => 'marketing_and_advertising',
        'growth marketer' => 'marketing_and_advertising',
        'growth marketing' => 'marketing_and_advertising',
        'growth hacker' => 'marketing_and_advertising',
        'performance marketer' => 'marketing_and_advertising',
        'media buyer' => 'marketing_and_advertising',
        'marketing manager' => 'marketing_and_advertising',
        'marketing specialist' => 'marketing_and_advertising',
        'маркетинговый менеджер' => 'marketing_and_advertising',
        'специалист по маркетингу' => 'marketing_and_advertising',
        'marketing mutaxassisi' => 'marketing_and_advertising',
        'marketing lead' => 'marketing_and_advertising',
        'marketing director' => 'marketing_and_advertising',
        'бренд-менеджер' => 'marketing_and_advertising',
        'smm manager' => 'marketing_and_advertising',
        'smm mutaxassisi' => 'marketing_and_advertising',
        'performance marketing specialist' => 'marketing_and_advertising',
        // Sales & Customer Relations
        'sales' => 'sales_and_customer_relations',
        'sale' => 'sales_and_customer_relations',
        'sales manager' => 'sales_and_customer_relations',
        'менеджер по продажам' => 'sales_and_customer_relations',
        'продажи' => 'sales_and_customer_relations',
        'продавец' => 'sales_and_customer_relations',
        'продаж' => 'sales_and_customer_relations',
        'sotuvchi' => 'sales_and_customer_relations',
        'sotuv' => 'sales_and_customer_relations',
        'savdo' => 'sales_and_customer_relations',
        'sales representative' => 'sales_and_customer_relations',
        'sales consultant' => 'sales_and_customer_relations',
        'inside sales' => 'sales_and_customer_relations',
        'field sales' => 'sales_and_customer_relations',
        'account manager' => 'sales_and_customer_relations',
        'client manager' => 'sales_and_customer_relations',
        'bizdev' => 'sales_and_customer_relations',
        'business development' => 'sales_and_customer_relations',
        'customer success' => 'sales_and_customer_relations',
        'client success' => 'sales_and_customer_relations',
        'relationship manager' => 'sales_and_customer_relations',
        'account executive' => 'sales_and_customer_relations',
        'key account manager' => 'sales_and_customer_relations',
        'partnership manager' => 'sales_and_customer_relations',
        'sales enablement' => 'sales_and_customer_relations',
        'менеджер по работе с клиентами' => 'sales_and_customer_relations',
        'client relationship manager' => 'sales_and_customer_relations',
        'account manager assistant' => 'sales_and_customer_relations',
        'assistant account manager' => 'sales_and_customer_relations',
        'sales assistant' => 'sales_and_customer_relations',
        'sales director' => 'sales_and_customer_relations',
        'sales lead' => 'sales_and_customer_relations',
        'client service manager' => 'sales_and_customer_relations',
        'менеджер клиентов' => 'sales_and_customer_relations',
        'менеджер по работе с партнерами' => 'sales_and_customer_relations',
        // IT & Software Development
        'developer' => 'it_and_software_development',
        'software developer' => 'it_and_software_development',
        'programmer' => 'it_and_software_development',
        'software engineer' => 'it_and_software_development',
        'software architect' => 'it_and_software_development',
        'backend developer' => 'it_and_software_development',
        'backend engineer' => 'it_and_software_development',
        'frontend developer' => 'it_and_software_development',
        'frontend engineer' => 'it_and_software_development',
        'lead frontend developer' => 'it_and_software_development',
        'frontend lead developer' => 'it_and_software_development',
        'fullstack developer' => 'it_and_software_development',
        'full stack developer' => 'it_and_software_development',
        'mobile developer' => 'it_and_software_development',
        'ios developer' => 'it_and_software_development',
        'android developer' => 'it_and_software_development',
        'dasturchi' => 'it_and_software_development',
        'разработчик' => 'it_and_software_development',
        'программист' => 'it_and_software_development',
        'it specialist' => 'it_and_software_development',
        'python developer' => 'it_and_software_development',
        'java developer' => 'it_and_software_development',
        'javascript developer' => 'it_and_software_development',
        'typescript developer' => 'it_and_software_development',
        'c++ programmer' => 'it_and_software_development',
        'c# programmer' => 'it_and_software_development',
        'salesforce developer' => 'it_and_software_development',
        'salesforce engineer' => 'it_and_software_development',
        'sap developer' => 'it_and_software_development',
        'sap consultant' => 'it_and_software_development',
        '1c developer' => 'it_and_software_development',
        'sharepoint developer' => 'it_and_software_development',
        'shopify developer' => 'it_and_software_development',
        'crm administrator' => 'it_and_software_development',
        'crm admin' => 'it_and_software_development',
        'crm specialist' => 'it_and_software_development',
        'crm manager' => 'it_and_software_development',
        'bitrix24' => 'it_and_software_development',
        'bitrix' => 'it_and_software_development',
        'node.js' => 'it_and_software_development',
        'nodejs' => 'it_and_software_development',
        'nestjs' => 'it_and_software_development',
        'expressjs' => 'it_and_software_development',
        'express' => 'it_and_software_development',
        'spring boot' => 'it_and_software_development',
        'springboot' => 'it_and_software_development',
        'hibernate' => 'it_and_software_development',
        'fastapi' => 'it_and_software_development',
        'flask developer' => 'it_and_software_development',
        'django developer' => 'it_and_software_development',
        'laravel developer' => 'it_and_software_development',
        'symfony developer' => 'it_and_software_development',
        'rails developer' => 'it_and_software_development',
        'ruby on rails' => 'it_and_software_development',
        'nestjs developer' => 'it_and_software_development',
        'go developer' => 'it_and_software_development',
        'golang developer' => 'it_and_software_development',
        'golang' => 'it_and_software_development',
        'rust developer' => 'it_and_software_development',
        'c++ developer' => 'it_and_software_development',
        'c# developer' => 'it_and_software_development',
        'unity developer' => 'it_and_software_development',
        'unreal developer' => 'it_and_software_development',
        'game developer' => 'it_and_software_development',
        'game programmer' => 'it_and_software_development',
        'ведущий программист 1с' => 'it_and_software_development',
        'программист 1с' => 'it_and_software_development',
        '1с developer' => 'it_and_software_development',
        'senior php developer' => 'it_and_software_development',
        'middle php developer' => 'it_and_software_development',
        'php developer' => 'it_and_software_development',
        'php-developer' => 'it_and_software_development',
        'программист php' => 'it_and_software_development',
        'php dasturchi' => 'it_and_software_development',
        'team lead' => 'it_and_software_development',
        'tech lead' => 'it_and_software_development',
        'backend lead' => 'it_and_software_development',
        'frontend lead' => 'it_and_software_development',
        'mobile lead' => 'it_and_software_development',
        'blockchain developer' => 'it_and_software_development',
        'solidity developer' => 'it_and_software_development',
        'web3 developer' => 'it_and_software_development',
        // Data & Analytics
        'data analyst' => 'data_science_and_analytics',
        'data analytics' => 'data_science_and_analytics',
        'business analyst' => 'data_science_and_analytics',
        'data scientist' => 'data_science_and_analytics',
        'аналитик' => 'data_science_and_analytics',
        'data engineer' => 'data_science_and_analytics',
        'bi analyst' => 'data_science_and_analytics',
        'sql analyst' => 'data_science_and_analytics',
        'machine learning engineer' => 'data_science_and_analytics',
        'ml engineer' => 'data_science_and_analytics',
        'ai engineer' => 'data_science_and_analytics',
        'analytics engineer' => 'data_science_and_analytics',
        'etl developer' => 'data_science_and_analytics',
        'data platform engineer' => 'data_science_and_analytics',
        'business intelligence' => 'data_science_and_analytics',
        'bi developer' => 'data_science_and_analytics',
        // Product & Project
        'product manager' => 'product_and_project_management',
        'product owner' => 'product_and_project_management',
        'product analyst' => 'product_and_project_management',
        'продуктовый менеджер' => 'product_and_project_management',
        'project manager' => 'product_and_project_management',
        'project lead' => 'product_and_project_management',
        'проектный менеджер' => 'product_and_project_management',
        'scrum master' => 'product_and_project_management',
        'pm' => 'product_and_project_management',
        'program manager' => 'product_and_project_management',
        'delivery manager' => 'product_and_project_management',
        'release manager' => 'product_and_project_management',
        'project business analyst' => 'product_and_project_management',
        'business analyst project' => 'product_and_project_management',
        // QA
        'qa' => 'qa_and_testing',
        'qa engineer' => 'qa_and_testing',
        'quality assurance' => 'qa_and_testing',
        'tester' => 'qa_and_testing',
        'test engineer' => 'qa_and_testing',
        'тестировщик' => 'qa_and_testing',
        'qa automation engineer' => 'qa_and_testing',
        'automation tester' => 'qa_and_testing',
        'test automation engineer' => 'qa_and_testing',
        // DevOps & Cloud
        'devops' => 'devops_and_cloud_engineering',
        'devops engineer' => 'devops_and_cloud_engineering',
        'cloud engineer' => 'devops_and_cloud_engineering',
        'sre' => 'devops_and_cloud_engineering',
        'site reliability' => 'devops_and_cloud_engineering',
        'infrastructure engineer' => 'devops_and_cloud_engineering',
        'platform engineer' => 'devops_and_cloud_engineering',
        'cloud architect' => 'devops_and_cloud_engineering',
        'kubernetes engineer' => 'devops_and_cloud_engineering',
        'site reliability engineer' => 'devops_and_cloud_engineering',
        'reliability engineer' => 'devops_and_cloud_engineering',
        // Cybersecurity
        'cybersecurity' => 'cybersecurity',
        'security engineer' => 'cybersecurity',
        'information security' => 'cybersecurity',
        'secops' => 'cybersecurity',
        'кибербезопасность' => 'cybersecurity',
        'безопасность' => 'cybersecurity',
        'soc analyst' => 'cybersecurity',
        'blue team' => 'cybersecurity',
        'red team' => 'cybersecurity',
        'pentester' => 'cybersecurity',
        // UI/UX
        'ui designer' => 'ui_ux_and_product_design',
        'ux designer' => 'ui_ux_and_product_design',
        'ui/ux designer' => 'ui_ux_and_product_design',
        'product designer' => 'ui_ux_and_product_design',
        'ux researcher' => 'ui_ux_and_product_design',
        'дизайнер ux' => 'ui_ux_and_product_design',
        'дизайн' => 'ui_ux_and_product_design',
        'figma' => 'ui_ux_and_product_design',
        'interaction designer' => 'ui_ux_and_product_design',
        'visual designer' => 'ui_ux_and_product_design',
        'ux writer' => 'ui_ux_and_product_design',
        // Content & Copywriting
        'copywriter' => 'content_and_copywriting',
        'content writer' => 'content_and_copywriting',
        'content manager' => 'content_and_copywriting',
        'editor' => 'content_and_copywriting',
        'editorial' => 'content_and_copywriting',
        'журналист' => 'content_and_copywriting',
        'контент менеджер' => 'content_and_copywriting',
        'контент-менеджер' => 'content_and_copywriting',
        'контентщик' => 'content_and_copywriting',
        'technical writer' => 'content_and_copywriting',
        'blogger' => 'content_and_copywriting',
        'копирайтер' => 'content_and_copywriting',
        // Video
        'video editor' => 'video_and_multimedia_production',
        'video production' => 'video_and_multimedia_production',
        'motion designer' => 'video_and_multimedia_production',
        'montajchi' => 'video_and_multimedia_production',
        'видеомонтаж' => 'video_and_multimedia_production',
        '3d animator' => 'video_and_multimedia_production',
        'video producer' => 'video_and_multimedia_production',
        'montajor' => 'video_and_multimedia_production',
        'монтажер' => 'video_and_multimedia_production',
        // Photography
        'photographer' => 'photography',
        'фотограф' => 'photography',
        'retoucher' => 'photography',
        // HR
        'human resources' => 'human_resources_and_recruitment',
        'hr manager' => 'human_resources_and_recruitment',
        'hr specialist' => 'human_resources_and_recruitment',
        'hr' => 'human_resources_and_recruitment',
        'recruiter' => 'human_resources_and_recruitment',
        'talent acquisition' => 'human_resources_and_recruitment',
        'кадровик' => 'human_resources_and_recruitment',
        'кадры' => 'human_resources_and_recruitment',
        'people partner' => 'human_resources_and_recruitment',
        'talent partner' => 'human_resources_and_recruitment',
        'hrbp' => 'human_resources_and_recruitment',
        'hr generalist' => 'human_resources_and_recruitment',
        'talent sourcer' => 'human_resources_and_recruitment',
        'headhunter' => 'human_resources_and_recruitment',
        // Finance & Accounting
        'accountant' => 'finance_and_accounting',
        'accounting' => 'finance_and_accounting',
        'finance' => 'finance_and_accounting',
        'financial analyst' => 'finance_and_accounting',
        'auditor' => 'finance_and_accounting',
        'бухгалтер' => 'finance_and_accounting',
        'финансист' => 'finance_and_accounting',
        'controller' => 'finance_and_accounting',
        'tax specialist' => 'finance_and_accounting',
        'treasurer' => 'finance_and_accounting',
        'financial controller' => 'finance_and_accounting',
        // Banking & Insurance
        'bank' => 'banking_and_insurance',
        'banking' => 'banking_and_insurance',
        'loan officer' => 'banking_and_insurance',
        'credit specialist' => 'banking_and_insurance',
        'insurance' => 'banking_and_insurance',
        'страхование' => 'banking_and_insurance',
        'страховой агент' => 'banking_and_insurance',
        'microfinance' => 'banking_and_insurance',
        'microcredit' => 'banking_and_insurance',
        'fintech analyst' => 'banking_and_insurance',
        'fintech product manager' => 'banking_and_insurance',
        'payment specialist' => 'banking_and_insurance',
        'credit officer' => 'banking_and_insurance',
        'loan consultant' => 'banking_and_insurance',
        'credit manager' => 'banking_and_insurance',
        'credit analyst' => 'banking_and_insurance',
        'kredit mutaxassisi' => 'banking_and_insurance',
        // Legal
        'lawyer' => 'legal_and_compliance',
        'legal' => 'legal_and_compliance',
        'jurist' => 'legal_and_compliance',
        'юрист' => 'legal_and_compliance',
        'legal counsel' => 'legal_and_compliance',
        'compliance' => 'legal_and_compliance',
        'corporate lawyer' => 'legal_and_compliance',
        // Administration
        'office manager' => 'administration_and_office_support',
        'office administrator' => 'administration_and_office_support',
        'administrator' => 'administration_and_office_support',
        'administrative assistant' => 'administration_and_office_support',
        'secretary' => 'administration_and_office_support',
        'receptionist' => 'administration_and_office_support',
        'офис менеджер' => 'administration_and_office_support',
        'администратор' => 'administration_and_office_support',
        'executive assistant' => 'administration_and_office_support',
        'assistant manager' => 'administration_and_office_support',
        'manager assistant' => 'administration_and_office_support',
        'manager yordamchisi' => 'administration_and_office_support',
        'помощник менеджера' => 'administration_and_office_support',
        'operator 1c' => 'administration_and_office_support',
        'оператор 1с' => 'administration_and_office_support',
        // Education
        'teacher' => 'education_and_training',
        'tutor' => 'education_and_training',
        'trainer' => 'education_and_training',
        'coach' => 'education_and_training',
        'lecturer' => 'education_and_training',
        'преподаватель' => 'education_and_training',
        'учитель' => 'education_and_training',
        'o\'qituvchi' => 'education_and_training',
        'mentor' => 'education_and_training',
        'instructor' => 'education_and_training',
        'ielts teacher' => 'education_and_training',
        'ielts tutor' => 'education_and_training',
        'ielts instructor' => 'education_and_training',
        'ielts coach' => 'education_and_training',
        'ielts o\'qituvchisi' => 'education_and_training',
        'english teacher' => 'education_and_training',
        'teacher of english' => 'education_and_training',
        'ingliz tili o\'qituvchisi' => 'education_and_training',
        // Healthcare & Medicine
        'doctor' => 'healthcare_and_medicine',
        'nurse' => 'healthcare_and_medicine',
        'physician' => 'healthcare_and_medicine',
        'medic' => 'healthcare_and_medicine',
        'педиатр' => 'healthcare_and_medicine',
        'shifokor' => 'healthcare_and_medicine',
        'hamshira' => 'healthcare_and_medicine',
        'paramedic' => 'healthcare_and_medicine',
        'physician assistant' => 'healthcare_and_medicine',
        'therapist' => 'healthcare_and_medicine',
        'physiotherapist' => 'healthcare_and_medicine',
        'occupational therapist' => 'healthcare_and_medicine',
        'midwife' => 'healthcare_and_medicine',
        // Pharmacy
        'pharmacist' => 'pharmacy',
        'аптекарь' => 'pharmacy',
        'dorixonachi' => 'pharmacy',
        'druggist' => 'pharmacy',
        // Dentistry
        'dentist' => 'dentistry',
        'стоматолог' => 'dentistry',
        'orthodontist' => 'dentistry',
        // Veterinary
        'veterinarian' => 'veterinary_care',
        'ветеринар' => 'veterinary_care',
        'zookeeper' => 'veterinary_care',
        // Manufacturing
        'manufacturing engineer' => 'manufacturing_and_industrial_engineering',
        'production engineer' => 'manufacturing_and_industrial_engineering',
        'industrial engineer' => 'manufacturing_and_industrial_engineering',
        'factory engineer' => 'manufacturing_and_industrial_engineering',
        'завод' => 'manufacturing_and_industrial_engineering',
        'инженер производства' => 'manufacturing_and_industrial_engineering',
        'production manager' => 'manufacturing_and_industrial_engineering',
        'plant manager' => 'manufacturing_and_industrial_engineering',
        // Mechanical & Maintenance
        'mechanic' => 'mechanical_and_maintenance_engineering',
        'service engineer' => 'mechanical_and_maintenance_engineering',
        'maintenance engineer' => 'mechanical_and_maintenance_engineering',
        'автомеханик' => 'mechanical_and_maintenance_engineering',
        'texnik' => 'mechanical_and_maintenance_engineering',
        'hvac engineer' => 'mechanical_and_maintenance_engineering',
        'mechatronics engineer' => 'mechanical_and_maintenance_engineering',
        // Electrical & Electronics
        'electrical engineer' => 'electrical_and_electronics_engineering',
        'electronics engineer' => 'electrical_and_electronics_engineering',
        'электрик' => 'electrical_and_electronics_engineering',
        'электроинженер' => 'electrical_and_electronics_engineering',
        'embedded engineer' => 'electrical_and_electronics_engineering',
        'hardware engineer' => 'electrical_and_electronics_engineering',
        // Construction & Architecture
        'construction engineer' => 'construction_and_architecture',
        'civil engineer' => 'construction_and_architecture',
        'architect' => 'construction_and_architecture',
        'строитель' => 'construction_and_architecture',
        'прораб' => 'construction_and_architecture',
        'quruvchi' => 'construction_and_architecture',
        'site manager' => 'construction_and_architecture',
        'bim manager' => 'construction_and_architecture',
        // Logistics & Supply Chain
        'logistics' => 'logistics_and_supply_chain',
        'logistician' => 'logistics_and_supply_chain',
        'логист' => 'logistics_and_supply_chain',
        'supply chain' => 'logistics_and_supply_chain',
        'supply chain manager' => 'logistics_and_supply_chain',
        'supply manager' => 'logistics_and_supply_chain',
        'supply planner' => 'logistics_and_supply_chain',
        'import specialist' => 'logistics_and_supply_chain',
        '3pl specialist' => 'logistics_and_supply_chain',
        // Warehouse & Procurement
        'warehouse' => 'warehouse_and_procurement',
        'storekeeper' => 'warehouse_and_procurement',
        'кладовщик' => 'warehouse_and_procurement',
        'procurement' => 'warehouse_and_procurement',
        'zakup' => 'warehouse_and_procurement',
        'sotib olish' => 'warehouse_and_procurement',
        'inventory manager' => 'warehouse_and_procurement',
        'fulfillment manager' => 'warehouse_and_procurement',
        'warehouse manager' => 'warehouse_and_procurement',
        // Transportation & Driving
        'driver' => 'transportation_and_driving',
        'driver courier' => 'transportation_and_driving',
        'delivery driver' => 'transportation_and_driving',
        'курьер' => 'transportation_and_driving',
        'водитель' => 'transportation_and_driving',
        'haydovchi' => 'transportation_and_driving',
        'dispatcher' => 'transportation_and_driving',
        'dispetcher' => 'transportation_and_driving',
        'диспетчер' => 'transportation_and_driving',
        'fleet manager' => 'transportation_and_driving',
        'delivery coordinator' => 'transportation_and_driving',
        // Customer Support & Call Center
        'customer support' => 'customer_support_and_call_center',
        'customer service' => 'customer_support_and_call_center',
        'support specialist' => 'customer_support_and_call_center',
        'call center' => 'customer_support_and_call_center',
        'helpdesk' => 'customer_support_and_call_center',
        'служба поддержки' => 'customer_support_and_call_center',
        'operator' => 'customer_support_and_call_center',
        'customer care' => 'customer_support_and_call_center',
        'support engineer' => 'customer_support_and_call_center',
        'call center operator' => 'customer_support_and_call_center',
        'customer care manager' => 'customer_support_and_call_center',
        // Hospitality & Tourism
        'hospitality' => 'hospitality_and_tourism',
        'hotel' => 'hospitality_and_tourism',
        'reception' => 'hospitality_and_tourism',
        'гостиница' => 'hospitality_and_tourism',
        'tourism' => 'hospitality_and_tourism',
        'travel agency' => 'hospitality_and_tourism',
        'gid' => 'hospitality_and_tourism',
        'concierge' => 'hospitality_and_tourism',
        'front office' => 'hospitality_and_tourism',
        'travel manager' => 'hospitality_and_tourism',
        'tour manager' => 'hospitality_and_tourism',
        'tour operator' => 'hospitality_and_tourism',
        'менеджер по туризму' => 'hospitality_and_tourism',
        'туризм менеджер' => 'hospitality_and_tourism',
        'tourism manager' => 'hospitality_and_tourism',
        'travel consultant' => 'hospitality_and_tourism',
        // Food & Beverage
        'chef' => 'food_and_beverage_service',
        'cook' => 'food_and_beverage_service',
        'oshpaz' => 'food_and_beverage_service',
        'povar' => 'food_and_beverage_service',
        'barista' => 'food_and_beverage_service',
        'bartender' => 'food_and_beverage_service',
        'waiter' => 'food_and_beverage_service',
        'официант' => 'food_and_beverage_service',
        'restaurant manager' => 'food_and_beverage_service',
        'kitchen manager' => 'food_and_beverage_service',
        'chef de partie' => 'food_and_beverage_service',
        'су-шеф' => 'food_and_beverage_service',
        // Retail & E-commerce
        'retail' => 'retail_and_ecommerce',
        'store manager' => 'retail_and_ecommerce',
        'магазин' => 'retail_and_ecommerce',
        'cashier' => 'retail_and_ecommerce',
        'кассир' => 'retail_and_ecommerce',
        'ecommerce' => 'retail_and_ecommerce',
        'online store' => 'retail_and_ecommerce',
        'sales associate' => 'retail_and_ecommerce',
        'shop assistant' => 'retail_and_ecommerce',
        'merchandiser' => 'retail_and_ecommerce',
        'store director' => 'retail_and_ecommerce',
        'shop director' => 'retail_and_ecommerce',
        'директор магазина' => 'retail_and_ecommerce',
        'до\'кон мудири' => 'retail_and_ecommerce',
        'do\'kon mudiri' => 'retail_and_ecommerce',
        'товаровед' => 'retail_and_ecommerce',
        'товаровед магазина' => 'retail_and_ecommerce',
        'продавец консультант' => 'retail_and_ecommerce',
        'продавец-консультант' => 'retail_and_ecommerce',
        'до\'кон администратори' => 'retail_and_ecommerce',
        'do\'kon administratori' => 'retail_and_ecommerce',
        'store supervisor' => 'retail_and_ecommerce',
        'shop supervisor' => 'retail_and_ecommerce',
        // Real Estate
        'real estate' => 'real_estate',
        'realtor' => 'real_estate',
        'риэлтор' => 'real_estate',
        'broker' => 'real_estate',
        'агент недвижимости' => 'real_estate',
        'property manager' => 'real_estate',
        'leasing consultant' => 'real_estate',
        // Beauty & Personal Care
        'beautician' => 'beauty_and_personal_care',
        'cosmetologist' => 'beauty_and_personal_care',
        'beauty master' => 'beauty_and_personal_care',
        'vizajist' => 'beauty_and_personal_care',
        'визажист' => 'beauty_and_personal_care',
        'barber' => 'beauty_and_personal_care',
        'sartarosh' => 'beauty_and_personal_care',
        'go\'zallik' => 'beauty_and_personal_care',
        'makeup artist' => 'beauty_and_personal_care',
        'lash maker' => 'beauty_and_personal_care',
        'nail master' => 'beauty_and_personal_care',
        'esthetician' => 'beauty_and_personal_care',
        'massage therapist' => 'beauty_and_personal_care',
        'spa therapist' => 'beauty_and_personal_care',
        // Sports & Fitness
        'fitness' => 'sports_and_fitness',
        'fitness trainer' => 'sports_and_fitness',
        'coach' => 'sports_and_fitness',
        'sports trainer' => 'sports_and_fitness',
        'тренер' => 'sports_and_fitness',
        'personal trainer' => 'sports_and_fitness',
        'fitness instructor' => 'sports_and_fitness',
        'sports therapist' => 'sports_and_fitness',
        'rehabilitation coach' => 'sports_and_fitness',
        'yoga instructor' => 'sports_and_fitness',
        // Agriculture & Farming
        'agriculture' => 'agriculture_and_farming',
        'farmer' => 'agriculture_and_farming',
        'фермер' => 'agriculture_and_farming',
        'agronomist' => 'agriculture_and_farming',
        'dehqon' => 'agriculture_and_farming',
        'qishloq xo\'jaligi' => 'agriculture_and_farming',
        'zootechnician' => 'agriculture_and_farming',
        'agro engineer' => 'agriculture_and_farming',
        'horticulture' => 'agriculture_and_farming',
        'greenhouse' => 'agriculture_and_farming',
        'agtech' => 'agriculture_and_farming',
    ];

    /**
     * Keyword buckets for inference (slug => list of phrases).
     */
    private array $keywordBuckets = [
        'marketing_and_advertising' => ['marketing', 'advertising', 'таргет', 'smm', 'seo', 'ppc', 'brand', 'growth marketing', 'performance marketing', 'media buying', 'campaign management', 'маркетинг', 'маркетолог', 'smm manager', 'marketing lead', 'marketing director'],
        'sales_and_customer_relations' => ['sales', 'sale', 'продаж', 'продажи', 'sotuv', 'account manager', 'client manager', 'bizdev', 'crm', 'pipeline', 'customer success', 'partnerships', 'менеджер по работе с клиентами', 'sales assistant', 'account manager assistant', 'sales director', 'sales lead', 'client service manager'],
        'it_and_software_development' => ['developer', 'programmer', 'software', 'dasturchi', 'разработчик', 'backend', 'frontend', 'fullstack', 'mobile', 'node', 'nestjs', 'express', 'spring boot', 'django', 'flask', 'laravel', 'symfony', 'rails', 'react', 'vue', 'angular', 'kotlin', 'swift', 'unity', 'unreal', 'golang', 'rust', 'typescript', 'salesforce', 'sap', '1c', 'shopify', 'crm', 'bitrix', 'blockchain', 'solidity', 'web3', 'team lead', 'tech lead'],
        'data_science_and_analytics' => ['data', 'analytics', 'analyst', 'аналитик', 'bi', 'sql', 'tableau', 'power bi', 'ml', 'machine learning', 'ai', 'tensorflow', 'pytorch', 'spark', 'airflow', 'looker', 'fraud', 'risk analytics'],
        'product_and_project_management' => ['product manager', 'project manager', 'scrum', 'kanban', 'product owner', 'pm', 'roadmap', 'backlog', 'program manager', 'delivery manager', 'agile coach', 'project business analyst', 'project coordinator', 'assistant project manager'],
        'qa_and_testing' => ['qa', 'tester', 'testing', 'test engineer', 'quality assurance', 'тестировщик', 'automation testing', 'selenium', 'cypress', 'pytest', 'playwright', 'postman', 'jmeter', 'load testing'],
        'devops_and_cloud_engineering' => ['devops', 'ci/cd', 'kubernetes', 'docker', 'terraform', 'sre', 'cloud', 'ansible', 'helm', 'prometheus', 'grafana', 'aws', 'gcp', 'azure', 'infrastructure as code'],
        'cybersecurity' => ['security', 'cybersecurity', 'soc', 'pentest', 'информационная безопасность', 'кибер', 'blue team', 'red team', 'appsec', 'siem', 'grc', 'iam'],
        'ui_ux_and_product_design' => ['ui', 'ux', 'figma', 'wireframe', 'prototype', 'дизайн', 'design system', 'sketch', 'adobe xd', 'invision', 'ux research', 'interaction design'],
        'content_and_copywriting' => ['copywriter', 'content', 'editor', 'журналист', 'контент', 'technical writer', 'blogger', 'storytelling', 'seo copywriting', 'копирайтер', 'контент-менеджер', 'content specialist', 'content strategist'],
        'video_and_multimedia_production' => ['video', 'motion', 'premiere', 'after effects', 'montaj', 'видео', 'davinci resolve', 'cinema 4d', 'blender', 'storyboard', 'montajor', 'мобилограф', 'mobilograf', 'videographer'],
        'photography' => ['photo', 'photography', 'фото', 'photographer', 'lightroom', 'retouch', 'studio shoot', 'camera'],
        'human_resources_and_recruitment' => ['hr', 'recruiter', 'talent', 'кадры', 'hr manager', 'people partner', 'talent partner', 'people ops', 'compensation', 'headhunter', 'talent sourcer'],
        'finance_and_accounting' => ['finance', 'accounting', 'accountant', 'бухгалтер', 'financial', 'audit', 'treasury', 'tax', 'controller', 'payroll', 'budgeting', 'chief accountant', 'financial manager'],
        'banking_and_insurance' => ['bank', 'credit', 'loan', 'insurance', 'страхование', 'банк', 'microfinance', 'underwriting', 'fintech', 'payments', 'merchant', 'kpi', 'credit specialist', 'loan consultant', 'credit officer', 'credit manager', 'kredit', 'кредитный специалист', 'специалист по кредитам', 'microlending'],
        'legal_and_compliance' => ['legal', 'lawyer', 'юрист', 'contract', 'compliance', 'juridical', 'legal counsel', 'paralegal', 'policy', 'юрисконсульт', 'legal advisor'],
        'administration_and_office_support' => ['office manager', 'administrator', 'secretary', 'администратор', 'reception', 'executive assistant', 'office assistant', 'office coordinator', 'office operations', 'assistant manager', 'помощник менеджера', 'manager yordamchisi', 'operator 1c', 'оператор 1с', 'administrative assistant'],
        'education_and_training' => ['teacher', 'tutor', 'trainer', 'coach', 'учитель', 'преподаватель', 'kurs', 'mentor', 'instructor', 'curriculum', 'edtech', 'ielts', 'ielts teacher', 'ielts tutor', 'ielts instructor', 'ielts coach', 'english teacher'],
        'healthcare_and_medicine' => ['doctor', 'nurse', 'clinic', 'hospital', 'medical', 'shifokor', 'hamshira', 'paramedic', 'radiology', 'therapist', 'physiotherapy', 'midwife'],
        'pharmacy' => ['pharmacy', 'pharmacist', 'аптека', 'dorixon', 'pharma', 'pharmacology', 'dispensing'],
        'dentistry' => ['dental', 'dentist', 'стоматолог'],
        'veterinary_care' => ['veterinary', 'vet', 'ветеринар', 'zoo', 'pet clinic'],
        'manufacturing_and_industrial_engineering' => ['manufacturing', 'production', 'factory', 'industrial', 'завод', 'lean', 'six sigma', 'production line'],
        'mechanical_and_maintenance_engineering' => ['mechanic', 'maintenance', 'service engineer', 'авто', 'texnik', 'hvac', 'mechatronics', 'automotive'],
        'electrical_and_electronics_engineering' => ['electrical', 'electronics', 'электро', 'электрик', 'embedded', 'hardware', 'plc', 'automation'],
        'construction_and_architecture' => ['construction', 'civil engineer', 'architect', 'строитель', 'qurilish', 'proekt', 'bim', 'site manager', 'structural'],
        'logistics_and_supply_chain' => ['logistics', 'supply chain', 'логист', 'post', 'delivery', '3pl', 'freight', 'import', 'export', 'fleet', 'distribution', 'logistics coordinator', 'supply planner'],
        'warehouse_and_procurement' => ['warehouse', 'storekeeper', 'клад', 'procurement', 'zakup', 'sotib olish', 'inventory', 'fulfillment', 'warehouse management', 'inventory manager', 'purchase manager'],
        'transportation_and_driving' => ['driver', 'driving', 'courier', 'delivery', 'водитель', 'haydovchi', 'dispatcher', 'dispetcher', 'диспетчер', 'fleet', 'chauffeur', 'delivery coordinator'],
        'customer_support_and_call_center' => ['support', 'customer service', 'call center', 'helpdesk', 'contact center', 'служба поддержки', 'customer care', 'ticketing', 'support engineer', 'customer care manager'],
        'hospitality_and_tourism' => ['hospitality', 'hotel', 'tour', 'travel', 'гостиница', 'gid', 'concierge', 'front office', 'resort', 'booking', 'tourism manager', 'tour operator', 'travel consultant', 'tour manager'],
        'food_and_beverage_service' => ['chef', 'cook', 'barista', 'waiter', 'кухня', 'oshpaz', 'bartender', 'restaurant', 'kitchen', 'culinary', 'kitchen manager', 'chef de partie'],
        'retail_and_ecommerce' => ['retail', 'store', 'shop', 'магазин', 'cashier', 'e-commerce', 'online store', 'merchandiser', 'sales associate', 'pos', 'merchandising', 'директор магазина', 'продавец консультант', 'товаровед', 'store director', 'store supervisor', 'shop supervisor'],
        'real_estate' => ['real estate', 'realtor', 'broker', 'недвижимость', 'риэлтор', 'property manager', 'leasing', 'property advisor'],
        'beauty_and_personal_care' => ['beauty', 'cosmetologist', 'barber', 'hair', 'визаж', 'go\'zallik', 'makeup', 'lashes', 'nail', 'spa', 'esthetician', 'lash maker', 'massage therapist'],
        'sports_and_fitness' => ['fitness', 'sport', 'coach', 'trainer', 'gym', 'personal trainer', 'yoga', 'rehabilitation', 'physiotherapy'],
        'agriculture_and_farming' => ['agriculture', 'farming', 'agro', 'фермер', 'dehqon', 'agronom', 'zootechnician', 'agribusiness', 'greenhouse', 'agtech'],
    ];

    private array $canonicalIndex = [];
    private array $aliasIndex = [];
    private array $slugIndex = [];

    public function __construct()
    {
        foreach ($this->categories as $slug => $label) {
            foreach ($this->makeKeys($label) as $key) {
                if ($key !== '') {
                    $this->canonicalIndex[$key] = $label;
                }
            }
            $slugKey = $this->slugify($label);
            if ($slugKey !== '') {
                $this->slugIndex[$slugKey] = $label;
            }
        }
        foreach ($this->aliasMap as $alias => $slug) {
            foreach ($this->makeKeys($alias) as $key) {
                if ($key !== '') {
                    $this->aliasIndex[$key] = $slug;
                }
            }
        }
    }

    /**
     * Categorize vacancy using primary category, title, description, and optional raw label.
     */
    public function categorize(?string $category, ?string $title, ?string $description = '', ?string $categoryRaw = ''): string
    {
        foreach ([$category, $categoryRaw] as $candidate) {
            $match = $this->matchDirect($candidate);
            if ($match !== null) {
                return $match;
            }
        }

        $inferred = $this->inferFromText((string) $title, (string) $description);
        if ($inferred !== null) {
            return $inferred;
        }

        return $this->categories['other'];
    }

    public function slugify(?string $label): string
    {
        if ($label === null || trim($label) === '') {
            return 'other';
        }
        return Str::slug(mb_strtolower($label, 'UTF-8'), '-');
    }

    public function fromSlug(?string $slug): ?string
    {
        if ($slug === null || trim($slug) === '') {
            return null;
        }
        $key = $this->slugify($slug);
        return $this->slugIndex[$key] ?? null;
    }

    private function matchDirect(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $keys = $this->makeKeys($value);
        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }
            if (isset($this->canonicalIndex[$key])) {
                return $this->canonicalIndex[$key];
            }
            if (isset($this->aliasIndex[$key])) {
                $slug = $this->aliasIndex[$key];
                return $this->categories[$slug] ?? null;
            }
        }

        return null;
    }

    private function inferFromText(string $title, string $description): ?string
    {
        $combined = trim($title . ' ' . $description);
        if ($combined === '') {
            return null;
        }
        $textKeys = $this->makeKeys($combined);
        $text = $textKeys[0] ?? '';
        if ($text === '') {
            return null;
        }

        foreach ($this->keywordBuckets as $slug => $phrases) {
            foreach ($phrases as $phrase) {
                $needle = $this->makeKeys($phrase)[0] ?? '';
                if ($needle !== '' && str_contains($text, $needle)) {
                    return $this->categories[$slug] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Generate sanitized lookup keys (lowercase, trimmed, punctuation reduced) plus ASCII fallback.
     *
     * @return array<int, string>
     */
    private function makeKeys(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $base = mb_strtolower(trim($value), 'UTF-8');
        $base = str_replace(['&', '+', '/', '|'], ' ', $base);
        $base = preg_replace('/[^a-z0-9\p{L}\s]+/u', ' ', $base);
        $base = preg_replace('/\s+/u', ' ', $base);
        $base = trim($base);

        $keys = [];
        if ($base !== '') {
            $keys[] = $base;
        }

        $ascii = Str::slug($base, ' ');
        if ($ascii !== '' && $ascii !== $base) {
            $keys[] = $ascii;
        }

        return array_values(array_unique($keys));
    }
}
