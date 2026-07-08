<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refresh descriptions for the 14 AI-focused curriculum modules with the
 * updated marketing copy.
 */
final class Version20260708130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refresh descriptions for the 14 AI-focused curriculum modules.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $descriptions = [
            'problem-framing'         => "Sharp problem framing is the foundation of every successful AI initiative. You'll learn to distinguish root causes from symptoms, identify real opportunities hidden inside messy situations, and scope challenges clearly enough to act on. The focus is practical judgment: knowing what's worth solving, what AI is suited for, and how to articulate problem and opportunity statements that open up better solutions.",
            'empathy-storytelling'    => "Getting AI ideas off the ground requires strong people skills alongside technical know-how. You'll practice surfacing the real needs behind stated ones, structuring messages that persuade and align, and facilitating productive working sessions. The goal is to communicate with clarity across any audience and make complex ideas easy to understand, buy into, and act on.",
            'ai-foundations'          => "This module builds a working mental model of how large language models actually work: how they generate output, why they hallucinate, and how token-based usage affects cost and performance. You'll map the major AI tools and platforms, evaluate them on capability, cost, and privacy fit, and develop the judgment to select the right tool for a given task with a clear rationale.",
            'prompt-context'          => "AI output quality is determined by input quality. This module turns prompting into a repeatable skill, covering prompt structure, iteration techniques, and diagnosing underperforming prompts. You'll also master context management: selecting the right information to include, cutting what's noise, and structuring inputs that consistently deliver better results while keeping token costs in check.",
            'data-literacy'           => "Working effectively with AI starts with being able to read and evaluate data. You'll learn to interpret charts and metrics correctly, spot common pitfalls like correlation mistaken for causation, and assess whether a dataset is fit for an AI application. You'll also analyze your own workflows for AI readiness, identifying where data quality and access are enabling value or blocking it.",
            'rapid-prototyping'       => "Speed from idea to evidence is a competitive advantage. You'll use AI-assisted prototyping to build tangible artifacts fast, including pages, visuals, features, and models, and design experiments that generate real signal about whether an idea holds up. Alongside that, you'll apply human-centered AI design principles to build experiences that are trustworthy, transparent, and genuinely usable.",
            'agentic-collaboration'   => "AI agents can execute complex, multi-step tasks, but effective use requires deliberate human oversight. You'll learn to delegate tasks to agents, decompose goals into agent-suitable steps, and maintain the right level of control throughout. The module covers how to design human + agent workflows with clear roles, defined handoffs, and the cost and quality guardrails that make agentic work reliable.",
            'ai-coding'               => "AI tools are changing how software gets built at every stage. You'll develop hands-on fluency with AI coding tools, from autocomplete to agentic coding across multiple files and systems. The module covers how to configure project memory, rules, and context setups that improve AI coding reliability, and how to design AI-native architectures that account for cost, latency, safety, and the right level of human control.",
            'ai-qa-testing'           => "Non-deterministic AI output requires a systematic approach to quality, not one-off checks. You'll use AI to generate and run test suites, design evaluation frameworks (\"evals\") that measure output against clear criteria, and build observability into AI systems so you can catch drift, cost issues, and quality problems early. The result is a repeatable quality practice you can rely on at scale.",
            'responsible-ai'          => "Deploying AI responsibly requires more than good intentions. You'll assess real AI use cases for bias, fairness, transparency, and accountability gaps, and apply practical oversight practices that keep AI use trustworthy. The module also covers key compliance requirements, including the EU AI Act, and how to handle data, secrets, and sensitive decisions in ways that reduce risk and earn lasting trust.",
            'ai-platform-engineering' => "Running AI reliably in production requires the right infrastructure beneath it. You'll build data and retrieval pipelines that supply AI features with clean, well-structured input, integrate AI into CI/CD and delivery workflows, and operate agents in sandboxed, isolated runtimes with monitoring, rollback, and incident response built in. This module covers the full stack needed to keep AI systems performant, cost-controlled, and production-grade.",
            'ai-security'             => "Agentic AI systems introduce security risks that traditional approaches aren't built to address. This module focuses on the threats specific to AI: prompt injection, jailbreaks, secret leakage, and over-permissioned agents. You'll apply secure-by-default practices to your own AI work, learn to threat-model and red-team AI features, and build the defenses that make agentic systems safe to ship and operate.",
            'process-reimagination'   => "This module moves beyond task automation to full process redesign. You'll map existing workflows, identify AI-suitable steps, and redesign how humans and AI divide work across a team or function. The focus is on prioritizing use cases by value, risk, and feasibility, and designing operating models that sustain AI ways of working as roles, structures, and tools evolve around them.",
            'ai-product-design'       => "This module covers how to design AI-native products from the ground up. You'll identify where AI shifts value in an offering, define what becomes newly possible, and translate that into clear, actionable product specifications. Throughout, you'll apply human-centered AI design principles to ensure what gets built is trustworthy, usable, and ready for real users, grounded in genuine user needs rather than technical capability alone.",
        ];

        foreach ($descriptions as $slug => $desc) {
            $this->addSql(
                "UPDATE module SET description = :desc WHERE slug = :slug",
                ['desc' => $desc, 'slug' => $slug]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // no-op — copy changes only
    }
}
