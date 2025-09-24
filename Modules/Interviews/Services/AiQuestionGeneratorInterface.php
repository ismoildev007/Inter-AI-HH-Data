<?php

namespace Modules\Interviews\Services;

interface AiQuestionGeneratorInterface
{
    /**
     * Generate interview questions from vacancy context.
     *
     * @param  string      $title
     * @param  string|null $company
     * @param  string|null $description
     * @param  string|null $language preferred language code (or 'auto')
     * @param  int         $count number of questions to generate
     * @return array<int,string>
     */
    public function generate(string $title, ?string $company, ?string $description, ?string $language, int $count = 20): array;
}

