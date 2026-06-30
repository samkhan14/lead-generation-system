<?php

namespace App\Domains\AI\Enums;

enum KnowledgeBaseCategory: string
{
    case Company = 'company';
    case Portfolio = 'portfolio';
    case CaseStudy = 'case_study';
    case Policy = 'policy';
    case Faq = 'faq';
    case Services = 'services';
    case Pricing = 'pricing';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
