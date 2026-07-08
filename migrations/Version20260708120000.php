<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add Ring entity + FK from skill.
 * Upsert skill anchor descriptions from the merged Anchors_Merged.csv (v1)
 * and add the two new skills: AI Data & Retrieval Engineering + AI & Agent Security Engineering.
 * Also reassigns builder-lens skills into the new 'ai-engineering-builders' domain.
 */
final class Version20260708120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ring entity + FK; upsert skills from Anchors_Merged.csv; add 2 new AI-engineering skills.';
    }

    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        // -- 1. Create ring table (idempotent) --
        $tables = $this->connection->executeQuery("SHOW TABLES LIKE 'ring'")->fetchAllAssociative();
        if (empty($tables)) {
            $this->addSql("CREATE TABLE ring (
                id INTEGER NOT NULL AUTO_INCREMENT,
                slug VARCHAR(40) NOT NULL,
                name VARCHAR(100) NOT NULL,
                position INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY(id),
                UNIQUE KEY uniq_ring_slug (slug)
            )");
        }

        // -- 2. Seed the 4 rings --
        $rings = [
            ['human-core',      'Durable Human Core', 1],
            ['working-with-ai', 'Working With AI',    2],
            ['leading-with-ai', 'Leading With AI',    3],
            ['enabler',         'Enabler Ring',       4],
        ];
        foreach ($rings as [$slug, $name, $pos]) {
            $this->addSql(
                "INSERT IGNORE INTO ring (slug, name, position) VALUES (:slug, :name, :pos)",
                ['slug' => $slug, 'name' => $name, 'pos' => $pos]
            );
        }

        // -- 3. Add ring_id FK on skill (nullable) --
        $cols = $this->connection->executeQuery("SHOW COLUMNS FROM skill LIKE 'ring_id'")->fetchAllAssociative();
        if (empty($cols)) {
            $this->addSql("ALTER TABLE skill ADD COLUMN ring_id INTEGER DEFAULT NULL");
            $this->addSql("ALTER TABLE skill ADD CONSTRAINT fk_skill_ring FOREIGN KEY (ring_id) REFERENCES ring(id)");
            $this->addSql("CREATE INDEX idx_skill_ring ON skill (ring_id)");
        }

        // -- 4. Backfill skill.ring_id from ring_slug --
        $this->addSql("UPDATE skill s
                       JOIN ring r ON r.slug = s.ring_slug
                       SET s.ring_id = r.id
                       WHERE s.ring_id IS NULL");

        // -- 5. Upsert skill data from CSV --
        $skills = [
            [
                'slug' => 'ai-identifying-and-framing-problems',
                'name' => 'Identifying & Framing Problems & Opportunities',
                'ringSlug' => 'human-core',
                'ringName' => 'Durable Human Core',
                'domainSlug' => 'critical-thinking',
                'domainName' => 'Critical Thinking',
                'capabilityKey' => 'critical-thinking',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands the difference between a problem, a symptom and a solution, and why framing problems and opportunities matters.","competent":"Uses a framing technique to articulate a clear problem or opportunity statement for a given situation.","proficient":"Breaks messy situations into root causes, assumptions and constraints; spots opportunities; and judges what is worth solving — and whether AI is the right route.","expert":"Formulates original, well-scoped problem and opportunity statements that reframe challenges and open new solution spaces, and coaches others."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-judgment-and-verification',
                'name' => 'Judgment & Verification',
                'ringSlug' => 'human-core',
                'ringName' => 'Durable Human Core',
                'domainSlug' => 'critical-thinking',
                'domainName' => 'Critical Thinking',
                'capabilityKey' => 'critical-thinking',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands that AI output can be wrong, biased or fabricated; that a human stays accountable for it; and that over-relying on AI can erode one\'s own skills.","competent":"Applies a verification routine, keeps a human in the loop, and stays actively engaged rather than rubber-stamping AI output.","proficient":"Pinpoints errors, bias and risk; judges when to accept, verify, override — or not use AI at all; and balances AI use against maintaining their own judgment and expertise.","expert":"Designs verification, human-oversight and healthy-reliance practices (accountability, when-not-to-use guidance, avoiding skill atrophy) that keep AI use reliable across a team."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-learning-agility',
                'name' => 'Learning Agility',
                'ringSlug' => 'human-core',
                'ringName' => 'Durable Human Core',
                'domainSlug' => 'critical-thinking',
                'domainName' => 'Critical Thinking',
                'capabilityKey' => 'critical-thinking',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands that AI tools and practices change fast and that staying current matters.","competent":"Picks up a new AI tool or technique and uses it in real work.","proficient":"Compares new and old approaches and judges what to adopt, adapt or drop — separating signal from hype.","expert":"Builds a personal learning system that stays current and helps others learn too."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-empathy-and-understanding',
                'name' => 'Empathy & Understanding',
                'ringSlug' => 'human-core',
                'ringName' => 'Durable Human Core',
                'domainSlug' => 'human-centricity',
                'domainName' => 'Human-Centricity',
                'capabilityKey' => 'human-centricity',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands who the users and stakeholders are and their stated needs.","competent":"Uses interviews, observation and active listening to surface real needs.","proficient":"Distinguishes stated wants from underlying needs, detects unspoken concerns, and judges whose perspective should drive a decision.","expert":"Synthesizes diverse perspectives into a shared understanding that guides what to build."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-communication-and-storytelling',
                'name' => 'Communication & Storytelling',
                'ringSlug' => 'human-core',
                'ringName' => 'Durable Human Core',
                'domainSlug' => 'human-centricity',
                'domainName' => 'Human-Centricity',
                'capabilityKey' => 'human-centricity',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands what makes a message and a story clear.","competent":"Structures and delivers a clear message or story to an audience.","proficient":"Tailors a narrative to the audience and context, and judges whether it will land and persuade.","expert":"Crafts original, compelling narratives that align people and drive action."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-collaboration-and-facilitation',
                'name' => 'Collaboration & Facilitation',
                'ringSlug' => 'human-core',
                'ringName' => 'Durable Human Core',
                'domainSlug' => 'human-centricity',
                'domainName' => 'Human-Centricity',
                'capabilityKey' => 'human-centricity',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands collaboration norms and basic facilitation methods.","competent":"Applies facilitation techniques to run a productive working session.","proficient":"Reads group dynamics and judges which collaboration approach (including human + AI) fits a situation.","expert":"Designs collaborative ways of working that bring people and AI together effectively."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-literacy',
                'name' => 'AI Literacy',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-fluency',
                'domainName' => 'AI Fluency',
                'capabilityKey' => 'ai-fluency',
                'position' => 0,
                'descriptions' => '{"foundational":"Explains in plain terms what AI can and cannot do, and where it is appropriate, risky or unsuitable.","competent":"Chooses appropriate tasks for AI in everyday work.","proficient":"Analyses a task for AI-fit and judges the appropriateness and risk of using AI for a given purpose.","expert":"Shapes sensible guidance for when and how a team should (and shouldn\'t) use AI."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-mechanics-and-technologies',
                'name' => 'AI Mechanics & Technologies',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-fluency',
                'domainName' => 'AI Fluency',
                'capabilityKey' => 'ai-fluency',
                'position' => 1,
                'descriptions' => '{"foundational":"Describes at a working level how LLMs generate output and key concepts (tokens, context windows, training), why they hallucinate, and that AI is not free — usage consumes tokens and is increasingly billed by usage rather than flat subscription.","competent":"Uses knowledge of how AI works — including token and cost awareness — to get better, more reliable and more cost-efficient results.","proficient":"Reasons about why an AI produced a given output, and judges which AI approach or model suits a need, weighing capability against token and usage cost.","expert":"Explains AI mechanics — including the economics of token usage — to others, and designs simple mental models that make it accessible."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-tooling-landscape',
                'name' => 'AI Tooling Landscape',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-fluency',
                'domainName' => 'AI Fluency',
                'capabilityKey' => 'ai-fluency',
                'position' => 2,
                'descriptions' => '{"foundational":"Names the major AI tools and what each is for.","competent":"Uses the appropriate tool and surface for a common task.","proficient":"Compares tools across capability, cost, privacy and fit, and selects the right one with justification.","expert":"Assembles a personal or team AI toolkit with recommendations for different needs."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-prompting',
                'name' => 'Prompting',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-fluency',
                'domainName' => 'AI Fluency',
                'capabilityKey' => 'ai-fluency',
                'position' => 3,
                'descriptions' => '{"foundational":"Understands the basic elements of a prompt and why structure improves output.","competent":"Applies prompting techniques to get useful results, and refines when they fall short.","proficient":"Diagnoses why a prompt underperformed and judges between alternative prompting strategies.","expert":"Designs reusable prompts, templates and patterns for themselves and others."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-context-management',
                'name' => 'Context Management',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-fluency',
                'domainName' => 'AI Fluency',
                'capabilityKey' => 'ai-fluency',
                'position' => 4,
                'descriptions' => '{"foundational":"Understands that the information given to an AI shapes its output, and that too little or too much context affects both quality and cost.","competent":"Provides the right context (documents, examples, constraints) for a task.","proficient":"Analyses what context is relevant versus noise, and judges how much to include to balance relevance, creativity and token/usage cost.","expert":"Designs context setups (projects, knowledge, reusable briefs) that consistently improve output."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-data-literacy',
                'name' => 'Data Literacy',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-fluency',
                'domainName' => 'AI Fluency',
                'capabilityKey' => 'ai-fluency',
                'position' => 5,
                'descriptions' => '{"foundational":"Understands basic data terms, charts and metrics and what they show.","competent":"Uses data to answer a question or support a decision.","proficient":"Analyses data for patterns and pitfalls (e.g., correlation vs causation), and judges whether it supports a claim or is fit for an AI use.","expert":"Designs a simple measurement or experiment to generate the evidence needed."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-augmenting-everyday-work-with-ai',
                'name' => 'Augmenting Everyday Work with AI',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-powered-work',
                'domainName' => 'AI-Powered Work',
                'capabilityKey' => 'ai-powered-work',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands that AI can speed up and improve everyday knowledge work, and recognizes which of their own tasks it suits.","competent":"Uses AI to do real recurring tasks — drafting, summarizing, researching and synthesizing, analyzing, preparing materials, planning — with good results.","proficient":"Analyses their own workflow to apply AI where it adds most value, and judges output quality before relying on it — without over-relying or letting their own skills atrophy.","expert":"Designs AI-augmented personal and team working routines, and shares reusable approaches that lift everyone\'s everyday productivity."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-rapid-prototyping-and-experimentation',
                'name' => 'Rapid Prototyping & Experimentation',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-powered-work',
                'domainName' => 'AI-Powered Work',
                'capabilityKey' => 'ai-powered-work',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands how AI-assisted prototyping shortens the idea-to-evidence loop, for any kind of output (not just software).","competent":"Builds a simple prototype with AI tools — a page, copy, a visual, a model or a feature — to make an idea tangible.","proficient":"Analyses prototype and experiment results, and judges whether the evidence supports advancing or killing an idea.","expert":"Designs and builds prototypes and experiments that validate ideas quickly."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-centric-way-of-working',
                'name' => 'AI-Centric Way-of-Working',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-powered-work',
                'domainName' => 'AI-Powered Work',
                'capabilityKey' => 'ai-powered-work',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands how AI changes architecture, development, testing and delivery.","competent":"Applies AI tools to their part of the lifecycle in standard cases.","proficient":"Analyses where AI helps or hurts quality and speed, and judges when to rely on AI versus keep human control.","expert":"Designs an AI-native way of working for a team across the whole lifecycle."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-working-with-ai-agents',
                'name' => 'Working With AI Agents',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-powered-work',
                'domainName' => 'AI-Powered Work',
                'capabilityKey' => 'ai-powered-work',
                'position' => 3,
                'descriptions' => '{"foundational":"Understands what an AI agent is, what agents can and can\'t do, the risks of autonomy, and that agents can consume tokens and usage cost quickly.","competent":"Directs and delegates a multi-step task to an AI agent and supervises the result.","proficient":"Breaks a goal into agent-suitable steps; judges when to trust an agent to act, when to stay in the loop, and whether its output is sound; and keeps token and usage cost under control.","expert":"Designs and orchestrates multi-agent or tool-using setups that get reliable work done with appropriate human control."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-quality-and-evaluation',
                'name' => 'AI Quality & Evaluation',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-powered-work',
                'domainName' => 'AI-Powered Work',
                'capabilityKey' => 'ai-powered-work',
                'position' => 4,
                'descriptions' => '{"foundational":"Understands why AI\'s non-deterministic output needs deliberate, repeatable quality checks (not one-off testing).","competent":"Applies an existing evaluation or test to check AI output against criteria.","proficient":"Analyses evaluation results to locate quality issues, and judges whether a system is good enough to ship against defined criteria.","expert":"Designs evaluation systems (\\"evals\\") that measure and improve AI quality over time."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-designing-human-centered-ai-experiences',
                'name' => 'Designing Human-Centered AI Experiences',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-powered-work',
                'domainName' => 'AI-Powered Work',
                'capabilityKey' => 'ai-powered-work',
                'position' => 5,
                'descriptions' => '{"foundational":"Understands the patterns that make an AI experience trustworthy and usable (trust cues, control, error states).","competent":"Applies known patterns to design a basic AI interaction.","proficient":"Analyses an AI experience for trust, control and transparency gaps, and judges whether it keeps users informed, in control and safe.","expert":"Designs original AI experiences that build trust, handle uncertainty, and serve both humans and AI agents (including discoverability by AI)."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-powered-coding',
                'name' => 'AI-Powered Coding',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands what AI coding tools do (autocomplete, chat, agentic coding) and where they help or mislead; has tried them on real code.","competent":"Uses autocomplete, chat and in-IDE agentic coding to write and change code day to day, and reviews AI output before committing.","proficient":"Completes features across multiple files with agentic coding — steering and correcting the agent — and runs AI-assisted code review and test generation as routine practice.","expert":"Ships substantial, cross-system work primarily through agentic coding; sets the team\'s coding-with-AI patterns, guardrails and review standards."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-context-and-agent-configuration-engineering',
                'name' => 'Context & Agent Configuration Engineering',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands that project memory, rules and prompt files shape AI coding results, and can follow an existing setup (CLAUDE.md, AGENTS.md, Cursor rules).","competent":"Authors and maintains project memory, rules and reusable prompts for their own work, improving the reliability of AI tools.","proficient":"Designs custom skills, commands and context/harness setups for a codebase, and tunes them from observed agent behaviour.","expert":"Establishes context-and-configuration standards (memory, rules, skills, harnesses) the whole team builds on, and evolves them as tools change."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-engineering-agentic-systems',
                'name' => 'Engineering Agentic Systems',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands what agents, MCP tools and subagents are, and the risks of letting them act; has run a multi-step agent task.","competent":"Wires up MCP servers / tool integrations and uses subagents or background agents for real tasks, supervising results and cost.","proficient":"Designs multi-agent or tool-using workflows with clear roles, guardrails and cost control, and debugs them when they misbehave.","expert":"Architects reliable agentic systems (multi-agent orchestration, async/background agents) others depend on, with guardrails, evals and cost controls built in."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-native-architecture',
                'name' => 'AI-Native Architecture',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 3,
                'descriptions' => '{"foundational":"Understands how AI/agentic, non-deterministic systems differ from deterministic software (latency, cost, drift, autonomy vs. control).","competent":"Applies AI-native patterns to a component — choosing where AI fits, how data flows, and where humans stay in control.","proficient":"Designs the architecture for an AI feature or service, reasoning explicitly about cost, latency, reliability, safety and oversight trade-offs.","expert":"Sets AI-native architectural direction across systems — multi-agent design, integration, and capability-vs-safety trade-offs — and guides others."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-data-and-retrieval-engineering',
                'name' => 'AI Data & Retrieval Engineering',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 4,
                'descriptions' => '{"foundational":"Understands that AI/LLM features depend on well-prepared data and retrieval (chunking, embeddings, vector stores) and that data quality drives output quality.","competent":"Builds basic data and retrieval pipelines under guidance — ingests and chunks documents, generates embeddings, queries a vector store, and cleans/structures data for an AI feature.","proficient":"Independently designs data and retrieval pipelines — chunking/embedding strategy, hybrid search and re-ranking, freshness and retrieval-quality evaluation — handling scale, cost and data governance.","expert":"Architects the data and retrieval platform for AI — ingestion, vector/feature stores, retrieval-quality and governance standards — sets patterns others reuse and co-owns with Data & AI."}',
                'isNew' => true,
            ],
            [
                'slug' => 'ai-ai-augmented-testing-and-qa',
                'name' => 'AI-Augmented Testing & QA',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 5,
                'descriptions' => '{"foundational":"Understands how AI can generate tests and drive browser/QA testing, and the limits of AI-generated tests.","competent":"Uses AI to generate tests and surface edge cases for their own code, checking the tests are meaningful.","proficient":"Builds AI-augmented test suites (incl. browser-testing agents) into the workflow, and judges coverage and quality.","expert":"Designs the team\'s AI-augmented testing/QA approach — balancing speed with genuine assurance — and sets quality gates."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-in-devops-and-delivery',
                'name' => 'AI in DevOps & Delivery (LLMOps)',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 6,
                'descriptions' => '{"foundational":"Understands what it takes to run AI/agents in production (deployment, monitoring, rollback) and how it differs from classic ops.","competent":"Uses AI in CI/CD and delivery tasks, and helps operate AI features with basic monitoring.","proficient":"Builds AI into delivery pipelines and runs AI/agents in production with monitoring, rollback and incident handling.","expert":"Designs the org\'s LLMOps approach — deployment, observability, cost/SLA management and incident response for agents at scale."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-quality-evals-and-observability',
                'name' => 'AI Quality, Evals & Observability',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 7,
                'descriptions' => '{"foundational":"Understands why non-deterministic systems need evals and observability, not one-off tests.","competent":"Applies existing evals and reads tracing/monitoring to check AI behaviour.","proficient":"Designs eval suites and instruments tracing and drift/cost/jailbreak monitoring for an AI system, and judges release-readiness.","expert":"Builds production-grade eval + observability that gate releases and continuously improve quality, and sets the standard for the team."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-runtime-and-sandbox-infrastructure',
                'name' => 'AI Runtime & Sandbox Infrastructure',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 8,
                'descriptions' => '{"foundational":"Understands why agents need isolated, safe runtimes and what sandboxing protects against.","competent":"Runs agents in an existing sandbox/runtime (E2B, Modal, containers) for their own tasks.","proficient":"Sets up sandboxed execution environments with appropriate isolation, permissions and resource limits for agent work.","expert":"Builds and operates the org\'s agent-runtime platform — secure, scalable, isolated execution that others rely on. (Co-brand with XST / Data & AI.)"}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-and-agent-security-engineering',
                'name' => 'AI & Agent Security Engineering (VulnOps)',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 9,
                'descriptions' => '{"foundational":"Understands AI-specific security risks — prompt injection, jailbreaks, data and secret leakage, over-broad agent permissions — and why agentic systems widen the attack surface.","competent":"Applies secure-by-default practices to their own AI work: input/output guarding, correct secret handling, least-privilege tool and agent scopes, and sanctioned runtimes; escalates suspected issues.","proficient":"Designs defences for an AI feature or agent — prompt-injection mitigation, authentication/authorization for tools and agents, sandboxed execution and red-team testing — and judges whether it is safe to ship.","expert":"Sets the team\'s AI security-engineering standards — threat models, agent-auth patterns, guardrail and red-teaming practice, sandboxed-runtime requirements — and coaches others to build secure agentic systems."}',
                'isNew' => true,
            ],
            [
                'slug' => 'ai-autonomous-delivery-pipelines',
                'name' => 'Autonomous Delivery Pipelines (Dark Factory)',
                'ringSlug' => 'working-with-ai',
                'ringName' => 'Working With AI',
                'domainSlug' => 'ai-engineering-builders',
                'domainName' => 'AI Engineering (Builders)',
                'capabilityKey' => 'ai-engineering-builders',
                'position' => 10,
                'descriptions' => '{"foundational":"Understands the spec-to-PR / \\"dark factory\\" concept and where autonomy is (and isn\'t) appropriate.","competent":"Runs parts of delivery autonomously (e.g., agent-generated PRs) with human review at the gate.","proficient":"Designs a governed autonomous pipeline for a workflow — agents build and review, humans approve at defined gates — with evals and rollback.","expert":"Operates a governed autonomous (spec-to-PR) pipeline in production, with oversight, safety and quality controls — the L5 frontier. (Co-brand with XST / Data & AI.)"}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-strategy',
                'name' => 'AI Strategy',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'ai-strategizing',
                'domainName' => 'AI Strategizing',
                'capabilityKey' => 'ai-strategizing',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands how AI can create value and connect to business outcomes.","competent":"Applies a strategy frame to articulate where AI should focus in their area.","proficient":"Analyses the business to find where AI creates most value, and judges and prioritizes competing directions.","expert":"Formulates a focused, coherent AI strategy and set of choices."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-identifying-and-prioritizing-ai-use-cases',
                'name' => 'Identifying & Prioritizing AI Use-Cases',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'ai-strategizing',
                'domainName' => 'AI Strategizing',
                'capabilityKey' => 'ai-strategizing',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands how to assess a use-case on value and feasibility.","competent":"Applies a scoring approach to a set of candidate use-cases.","proficient":"Analyses use-cases for value, risk, effort and dependencies, and prioritizes what to pursue, scale or stop.","expert":"Builds a prioritized use-case portfolio tied to outcomes."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-designing-ai-centric-products-and-services',
                'name' => 'Designing AI-Centric Products & Services',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'ai-strategizing',
                'domainName' => 'AI Strategizing',
                'capabilityKey' => 'ai-strategizing',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands how AI can change what an offering does and the value it creates.","competent":"Applies AI ideas to enhance an existing offering.","proficient":"Analyses where value migrates and what AI makes newly possible, and judges the viability of a value proposition.","expert":"Designs a reimagined, AI-centric product or service offering."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ai-value-optimization',
                'name' => 'AI Value Optimization',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'ai-strategizing',
                'domainName' => 'AI Strategizing',
                'capabilityKey' => 'ai-strategizing',
                'position' => 3,
                'descriptions' => '{"foundational":"Understands how to define and measure the value of an AI initiative, including its usage/token cost base.","competent":"Applies metrics to track an AI initiative\'s value.","proficient":"Analyses results to see where value is and isn\'t realized, and judges whether to continue or scale.","expert":"Designs a value-measurement and optimization approach for AI initiatives."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-process-redesign',
                'name' => 'Process Redesign',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'transforming-work',
                'domainName' => 'Transforming Work',
                'capabilityKey' => 'transforming-work',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands the difference between automating a task and redesigning a process.","competent":"Maps a current workflow and identifies AI opportunities.","proficient":"Analyses a workflow for waste, bottlenecks and AI-suitable steps, and judges which redesign delivers the most improvement.","expert":"Redesigns an end-to-end workflow around human + AI collaboration."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-human-plus-ai-workflows',
                'name' => 'Human + AI Workflows',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'transforming-work',
                'domainName' => 'Transforming Work',
                'capabilityKey' => 'transforming-work',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands where humans should stay in the loop and why.","competent":"Applies a human-in-the-loop pattern to a simple workflow.","proficient":"Analyses a task to decide which parts AI executes and which humans own, and judges whether the division is effective, safe and trusted.","expert":"Designs human + AI workflows with clear roles, hand-offs and oversight."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-rethink-product-development',
                'name' => 'Rethink Product Development',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'transforming-work',
                'domainName' => 'Transforming Work',
                'capabilityKey' => 'transforming-work',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands how AI changes the cost and speed of building, and challenges backlogs, specs and prioritization.","competent":"Applies AI to part of the product-development process (e.g., faster prototyping).","proficient":"Analyses where current product practices break down with AI, and judges what to keep, change or drop.","expert":"Designs a rethought product-development approach for AI-enabled teams."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-operating-model',
                'name' => 'Operating Model',
                'ringSlug' => 'leading-with-ai',
                'ringName' => 'Leading With AI',
                'domainSlug' => 'transforming-work',
                'domainName' => 'Transforming Work',
                'capabilityKey' => 'transforming-work',
                'position' => 3,
                'descriptions' => '{"foundational":"Understands how AI and agents change roles and team structures.","competent":"Applies role and structure changes for AI in a small scope.","proficient":"Analyses where the current operating model blocks AI value, and judges operating-model options.","expert":"Designs a human + agent operating model that sustains AI ways of working."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-driving-transparency-and-ethical-ai-usage',
                'name' => 'Driving Transparency & Ethical AI Usage',
                'ringSlug' => 'enabler',
                'ringName' => 'Enabler Ring',
                'domainSlug' => 'cross-cutting',
                'domainName' => 'Cross-cutting',
                'capabilityKey' => 'cross-cutting',
                'position' => 0,
                'descriptions' => '{"foundational":"Understands common AI ethical risks and sources of bias, and what transparency and human oversight mean for AI.","competent":"Applies basic checks to reduce bias, and applies transparency and oversight practices in their AI use.","proficient":"Analyses an AI use for fairness, bias and where trust is gained or lost, and judges whether it is ethical, transparent and accountable enough.","expert":"Establishes practices that embed ethics, reduce bias by design, and earn and sustain trust across a team."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-ensuring-safety-and-compliance',
                'name' => 'Ensuring Safety & Compliance (Responsible Use)',
                'ringSlug' => 'enabler',
                'ringName' => 'Enabler Ring',
                'domainSlug' => 'cross-cutting',
                'domainName' => 'Cross-cutting',
                'capabilityKey' => 'cross-cutting',
                'position' => 1,
                'descriptions' => '{"foundational":"Understands key AI-specific risks (prompt injection, data leakage), privacy concerns, and that rules apply (e.g., the EU AI Act).","competent":"Applies safe-use and required-control practices — handling data and secrets correctly, using sanctioned tools — and knows when to escalate.","proficient":"Analyses an AI use or feature for security, privacy, risk and compliance gaps, and judges whether it is safe and compliant enough.","expert":"Designs mitigations, safe-use practices and lightweight risk/compliance routines for AI features, tools and teams."}',
                'isNew' => false,
            ],
            [
                'slug' => 'ai-leading-by-example-to-optimize-ai-adoption',
                'name' => 'Leading by Example / AI Adoption & Readiness',
                'ringSlug' => 'enabler',
                'ringName' => 'Enabler Ring',
                'domainSlug' => 'cross-cutting',
                'domainName' => 'Cross-cutting',
                'capabilityKey' => 'cross-cutting',
                'position' => 2,
                'descriptions' => '{"foundational":"Understands why access to AI doesn\'t equal adoption, what makes a team ready, and why visible, responsible role-modelling matters.","competent":"Uses AI openly and responsibly as an example, and applies adoption and readiness practices in their team.","proficient":"Analyses where and why adoption stalls and where a team\'s readiness gaps are, and judges which interventions and behaviours will most improve adoption.","expert":"Designs and leads an adoption effort — role-modelling, readiness-building and coaching — that turns access into sustained value across the organization."}',
                'isNew' => false,
            ],
        ];

        foreach ($skills as $s) {
            if ($s['isNew']) {
                // Insert new skill under the 'ai' category. view_scope defaults to 'common'.
                $this->addSql(
                    "INSERT IGNORE INTO skill
                       (slug, name, ring_slug, ring_name, domain_slug, domain_name,
                        capability_key, view_scope, position, descriptions, category_id, ring_id)
                     SELECT :slug, :name, :ringSlug, :ringName, :domainSlug, :domainName,
                            :capabilityKey, 'common', :position, :descriptions, c.id, r.id
                     FROM category c, ring r
                     WHERE c.slug = 'ai' AND r.slug = :ringSlug",
                    [
                        'slug' => $s['slug'],
                        'name' => $s['name'],
                        'ringSlug' => $s['ringSlug'],
                        'ringName' => $s['ringName'],
                        'domainSlug' => $s['domainSlug'],
                        'domainName' => $s['domainName'],
                        'capabilityKey' => $s['capabilityKey'],
                        'position' => $s['position'],
                        'descriptions' => $s['descriptions'],
                    ]
                );
            } else {
                // Update existing skill: name, ring assignment, domain assignment,
                // capability_key, position, and anchor descriptions (from CSV).
                $this->addSql(
                    "UPDATE skill s
                     LEFT JOIN ring r ON r.slug = :ringSlug
                     SET s.name = :name,
                         s.ring_slug = :ringSlug,
                         s.ring_name = :ringName,
                         s.domain_slug = :domainSlug,
                         s.domain_name = :domainName,
                         s.capability_key = :capabilityKey,
                         s.position = :position,
                         s.descriptions = :descriptions,
                         s.ring_id = r.id
                     WHERE s.slug = :slug",
                    [
                        'slug' => $s['slug'],
                        'name' => $s['name'],
                        'ringSlug' => $s['ringSlug'],
                        'ringName' => $s['ringName'],
                        'domainSlug' => $s['domainSlug'],
                        'domainName' => $s['domainName'],
                        'capabilityKey' => $s['capabilityKey'],
                        'position' => $s['position'],
                        'descriptions' => $s['descriptions'],
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM skill WHERE slug IN ('ai-ai-data-and-retrieval-engineering', 'ai-ai-and-agent-security-engineering')");
        $this->addSql("ALTER TABLE skill DROP FOREIGN KEY fk_skill_ring");
        $this->addSql("DROP INDEX idx_skill_ring ON skill");
        $this->addSql("ALTER TABLE skill DROP COLUMN ring_id");
        $this->addSql("DROP TABLE ring");
    }
}