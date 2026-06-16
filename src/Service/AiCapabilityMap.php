<?php

namespace App\Service;

class AiCapabilityMap
{
    public const CAPABILITIES = [
        'ai-fluency' => [
            'name' => 'AI Fluency',
            'color' => '#C81D8F',
            'kind' => 'domain',
            'tag' => 'AI Fluency',
        ],
        'ai-powered-work' => [
            'name' => 'AI-Powered Work',
            'color' => '#138C7E',
            'kind' => 'domain',
            'tag' => 'AI-Powered Work',
        ],
        'ai-strategizing' => [
            'name' => 'AI Strategizing',
            'color' => '#2A47C9',
            'kind' => 'domain',
            'tag' => 'AI Strategizing',
        ],
        'transforming-work' => [
            'name' => 'Transforming Work',
            'color' => '#C28A2E',
            'kind' => 'domain',
            'tag' => 'Transforming Work',
        ],
        'critical-thinking' => [
            'name' => 'Critical Thinking',
            'color' => '#2A1453',
            'kind' => 'core',
            'tag' => 'Critical Thinking',
        ],
        'human-centricity' => [
            'name' => 'Human-Centricity',
            'color' => '#C44A20',
            'kind' => 'core',
            'tag' => 'Human-Centricity',
        ],
        'cross-cutting' => [
            'name' => 'Cross-cutting arrows (Transparency & Ethics · Safety & Compliance · Adoption & Readiness)',
            'color' => '#E331D0',
            'kind' => 'enabler',
            'tag' => 'Cross-cutting',
        ],
    ];

    private const MODULE_MAP = [
        'ai-f-1' => 'ai-fluency',
        'ai-f-2' => 'ai-fluency',
        'ai-f-3' => 'ai-fluency',
        'ai-f-4' => 'ai-fluency',
        'ai-f-5' => 'ai-fluency',
        'ai-f-6' => 'human-centricity',
        'ai-i-1' => 'ai-strategizing',
        'ai-i-2' => 'ai-strategizing',
        'ai-i-3' => 'cross-cutting',
        'ai-i-4' => 'ai-strategizing',
        'ai-i-5' => 'ai-powered-work',
        'ai-i-6' => 'ai-powered-work',
        'ai-i-7' => 'ai-strategizing',
        'ai-i-8' => 'human-centricity',
        'ai-a-1' => 'ai-powered-work',
        'ai-a-2' => 'transforming-work',
        'ai-a-3' => 'cross-cutting',
        'ai-a-4' => 'transforming-work',
        'ai-a-5' => 'human-centricity',
        'ai-e-1' => 'ai-fluency',
        'ai-e-2' => 'ai-powered-work',
        'ai-e-3' => 'ai-powered-work',
        'ai-e-4' => 'ai-strategizing',
        'ai-e-5' => 'cross-cutting',
        'ai-e-6' => 'ai-powered-work',
        'ai-e-7' => 'cross-cutting',
    ];

    public function forModule(string $slug): ?string
    {
        return self::MODULE_MAP[$slug] ?? null;
    }

    public function all(): array
    {
        return self::CAPABILITIES;
    }
}
