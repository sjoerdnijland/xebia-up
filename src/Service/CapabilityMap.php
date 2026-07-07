<?php

namespace App\Service;

class CapabilityMap
{
    private const CAPABILITIES = [
        'ai' => [
            'ai-fluency' => ['name' => 'AI Fluency', 'color' => '#C81D8F', 'kind' => 'domain', 'tag' => 'AI Fluency'],
            'ai-powered-work' => ['name' => 'AI-Powered Work', 'color' => '#138C7E', 'kind' => 'domain', 'tag' => 'AI-Powered Work'],
            'ai-strategizing' => ['name' => 'AI Strategizing', 'color' => '#2A47C9', 'kind' => 'domain', 'tag' => 'AI Strategizing'],
            'transforming-work' => ['name' => 'Transforming Work', 'color' => '#C28A2E', 'kind' => 'domain', 'tag' => 'Transforming Work'],
            'critical-thinking' => ['name' => 'Critical Thinking', 'color' => '#2A1453', 'kind' => 'core', 'tag' => 'Critical Thinking'],
            'human-centricity' => ['name' => 'Human-Centricity', 'color' => '#C44A20', 'kind' => 'core', 'tag' => 'Human-Centricity'],
            'cross-cutting' => ['name' => 'Cross-cutting arrows (Transparency & Ethics · Safety & Compliance · Adoption & Readiness)', 'color' => '#E331D0', 'kind' => 'enabler', 'tag' => 'Cross-cutting'],
        ],
        'product' => [
            'product-strategy' => ['name' => 'Product Strategy', 'color' => '#2563EB', 'kind' => 'domain', 'tag' => 'Product Strategy'],
            'discovery' => ['name' => 'Discovery & Research', 'color' => '#7C3AED', 'kind' => 'domain', 'tag' => 'Discovery'],
            'delivery' => ['name' => 'Delivery & Execution', 'color' => '#059669', 'kind' => 'domain', 'tag' => 'Delivery'],
            'product-thinking' => ['name' => 'Product Thinking', 'color' => '#D97706', 'kind' => 'domain', 'tag' => 'Product Thinking'],
        ],
        'agile' => [
            'agile-foundations' => ['name' => 'Agile Foundations', 'color' => '#0EA5E9', 'kind' => 'domain', 'tag' => 'Foundations'],
            'workflow-improvement' => ['name' => 'Leadership', 'color' => '#8B5CF6', 'kind' => 'domain', 'tag' => 'Leadership'],
            'facilitation' => ['name' => 'Facilitation', 'color' => '#F59E0B', 'kind' => 'domain', 'tag' => 'Facilitation'],
            'coaching' => ['name' => 'Coaching', 'color' => '#EC4899', 'kind' => 'domain', 'tag' => 'Coaching'],
            'scaling' => ['name' => 'Scaling & Strategy', 'color' => '#10B981', 'kind' => 'domain', 'tag' => 'Scaling'],
        ],
        'cloud' => [
            'cloud-foundations' => ['name' => 'Cloud Foundations', 'color' => '#0369A1', 'kind' => 'domain', 'tag' => 'Foundations'],
            'architecture' => ['name' => 'Architecture & Infrastructure', 'color' => '#6D28D9', 'kind' => 'domain', 'tag' => 'Architecture'],
            'security-strategy' => ['name' => 'Security & Strategy', 'color' => '#B45309', 'kind' => 'domain', 'tag' => 'Security & Strategy'],
        ],
        'leadership' => [
            'people-teams' => ['name' => 'People & Teams', 'color' => '#DC2626', 'kind' => 'domain', 'tag' => 'People & Teams'],
            'strategy-change' => ['name' => 'Strategy & Change', 'color' => '#7C3AED', 'kind' => 'domain', 'tag' => 'Strategy & Change'],
            'influence-decision' => ['name' => 'Influence & Decision', 'color' => '#047857', 'kind' => 'domain', 'tag' => 'Influence & Decision'],
        ],
    ];

    private const MODULE_MAP = [
        // Xebia Up curriculum
        'problem-framing'          => 'critical-thinking',
        'empathy-storytelling'     => 'people-teams',
        'ai-foundations'           => 'ai-fluency',
        'prompt-context'           => 'ai-fluency',
        'data-literacy'            => 'ai-fluency',
        'rapid-prototyping'        => 'ai-powered-work',
        'agentic-collaboration'    => 'ai-powered-work',
        'ai-coding'                => 'ai-powered-work',
        'ai-qa-testing'            => 'ai-powered-work',
        'responsible-ai'           => 'cross-cutting',
        'ai-platform-engineering'  => 'ai-powered-work',
        'ai-security'              => 'cross-cutting',
        'process-reimagination'    => 'transforming-work',
        'ai-product-design'        => 'ai-strategizing',

        // Agile — scrum.org classes (seeded via migration)
        'sc-psm' => 'workflow-improvement',
        'sc-psm-a' => 'workflow-improvement',
        'sc-pspo' => 'workflow-improvement',
        'sc-pspo-a' => 'workflow-improvement',
        'sc-psk' => 'workflow-improvement',
        'sc-pal' => 'scaling',
        'sc-pal-ebm' => 'scaling',
        'sc-psm-ai' => 'workflow-improvement',
        'sc-pspo-ai' => 'workflow-improvement',
        'sc-psfs' => 'facilitation',
        'sc-ppdv' => 'facilitation',
    ];

    /** Capability overrides for modules that appear in multiple categories. */
    private const MODULE_CAT_MAP = [
        'problem-framing'         => ['ai' => 'critical-thinking',  'product' => 'discovery'],
        'empathy-storytelling'    => ['leadership' => 'people-teams', 'product' => 'discovery'],
        'rapid-prototyping'       => ['ai' => 'ai-powered-work',    'product' => 'discovery'],
        'ai-platform-engineering' => ['ai' => 'ai-powered-work',    'cloud' => 'architecture'],
        'ai-security'             => ['ai' => 'cross-cutting',      'cloud' => 'security-strategy'],
        'process-reimagination'   => ['ai' => 'transforming-work',  'leadership' => 'strategy-change'],
        'ai-product-design'       => ['ai' => 'ai-strategizing',    'product' => 'product-strategy'],

        // scrum.org classes with multiple categories
        'sc-pspo'    => ['product' => 'product-thinking', 'agile' => 'workflow-improvement'],
        'sc-pspo-a'  => ['product' => 'product-thinking', 'agile' => 'workflow-improvement'],
        'sc-ppdv'    => ['product' => 'discovery',        'agile' => 'facilitation'],
        'sc-pal'     => ['leadership' => 'people-teams',  'agile' => 'scaling'],
        'sc-pal-ebm' => ['leadership' => 'strategy-change', 'agile' => 'scaling'],
        'sc-psm-ai'  => ['ai' => 'ai-powered-work',       'agile' => 'workflow-improvement'],
        'sc-pspo-ai' => ['ai' => 'ai-powered-work',       'agile' => 'workflow-improvement'],
    ];

    public function allForCategory(string $categorySlug): array
    {
        return self::CAPABILITIES[$categorySlug] ?? [];
    }

    /** @deprecated use allForCategory('ai') */
    public function all(): array
    {
        return self::CAPABILITIES['ai'];
    }

    public function forModule(string $slug, ?string $category = null): ?string
    {
        if ($category !== null && isset(self::MODULE_CAT_MAP[$slug][$category])) {
            return self::MODULE_CAT_MAP[$slug][$category];
        }
        return self::MODULE_MAP[$slug] ?? null;
    }
}
