<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replace the legacy per-topic module catalog with the 14 new curriculum modules.
 * Scrum.org modules (slug LIKE 'sc-%') are preserved.
 */
final class Version20260707170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace legacy module catalog with 14 new curriculum modules; scrum.org modules preserved.';
    }

    public function up(Schema $schema): void
    {
        // ---- 1. Delete legacy modules and their dependents ----
        // Everything except scrum.org modules (sc-*) is removed.
        $this->addSql("DELETE b FROM booking b JOIN session s ON b.session_id = s.id JOIN module m ON s.module_id = m.id WHERE m.slug NOT LIKE 'sc-%'");
        $this->addSql("DELETE s FROM session s JOIN module m ON s.module_id = m.id WHERE m.slug NOT LIKE 'sc-%'");
        $this->addSql("DELETE mo FROM module_objective mo JOIN module m ON mo.module_id = m.id WHERE m.slug NOT LIKE 'sc-%'");
        $this->addSql("DELETE mc FROM module_category mc JOIN module m ON mc.module_id = m.id WHERE m.slug NOT LIKE 'sc-%'");
        $this->addSql("DELETE mr FROM module_role mr JOIN module m ON mr.module_id = m.id WHERE m.slug NOT LIKE 'sc-%'");
        $this->addSql("DELETE ms FROM module_skill ms JOIN module m ON ms.module_id = m.id WHERE m.slug NOT LIKE 'sc-%'");
        $this->addSql("DELETE FROM module WHERE slug NOT LIKE 'sc-%'");

        // ---- 2. Insert the 14 new modules ----
        // [slug, title, description, level, type, duration, position]
        $modules = [
            ['problem-framing',         'Problem Framing & Opportunity Discovery',        'Turn ambiguous business challenges into sharply framed problems and validated AI opportunities worth pursuing.', 'foundational', 'trainer-led', 4, 1],
            ['empathy-storytelling',    'Empathy, Communication & Storytelling',          'Build genuine understanding of users and stakeholders, and craft narratives that align people and move work forward.', 'foundational', 'self-paced', 4, 2],
            ['ai-foundations',          'AI Foundations, Models & Tooling',                "A jargon-free grounding in modern AI — how models work, what today's tooling can do, and where it genuinely creates value.", 'foundational', 'self-paced', 4, 3],
            ['prompt-context',          'Prompt Engineering & Context Management',        'Get reliably better results from AI by mastering the two levers that matter most — what you ask, and what you show it.', 'competent', 'trainer-led', 4, 4],
            ['data-literacy',           'Data Literacy & AI Readiness',                    'How data is collected, shaped and trusted — the raw material every AI initiative quietly depends on.', 'foundational', 'self-paced', 4, 5],
            ['rapid-prototyping',       'Rapid Prototyping, Experimentation & UX Design',  'Shorten the loop from idea to evidence — prototype AI experiences, run honest experiments and design for uncertainty.', 'competent', 'trainer-led', 8, 6],
            ['agentic-collaboration',   'Agents, Human–Agent Collaboration & Agentic Teams', 'Design and lead teams where humans and AI agents share the work — with clear roles, guardrails and trust in the middle.', 'proficient', 'trainer-led', 8, 7],
            ['ai-coding',               'AI-Assisted Software Engineering',                'Ship real code faster and better with autocomplete, chat and agentic coding — from first prompt to production-ready feature.', 'competent', 'trainer-led', 8, 8],
            ['ai-qa-testing',           'AI-Powered Quality Assurance & Testing',          'Use AI to generate tests and drive QA — and build the evals that keep non-deterministic systems honest in production.', 'competent', 'trainer-led', 8, 9],
            ['responsible-ai',          'Responsible AI: Ethics, Safety & Compliance',     'Turn ethics, safety and the EU AI Act from abstract concerns into practical controls teams can actually follow.', 'competent', 'trainer-led', 4, 10],
            ['ai-platform-engineering', 'AI Infrastructure & Platform Engineering',        'Build the runtime, delivery pipelines and sandboxes that let AI features and agents run safely, cheaply and reliably at scale.', 'proficient', 'trainer-led', 8, 11],
            ['ai-security',             'AI Security & Secure AI Systems',                 'Harden AI systems against prompt injection, data leakage and jailbreaks — before attackers get there first.', 'proficient', 'trainer-led', 8, 12],
            ['process-reimagination',   'Business Process Reimagination with AI',          'Go beyond automating tasks — redesign end-to-end workflows and operating models around human + AI collaboration.', 'proficient', 'trainer-led', 8, 13],
            ['ai-product-design',       'AI Product Design & Specification',               'Translate fuzzy AI ambitions into product specs that engineers, designers and data scientists can actually build against.', 'competent', 'trainer-led', 8, 14],
        ];

        foreach ($modules as [$slug, $title, $desc, $level, $type, $duration, $position]) {
            $this->addSql(
                "INSERT INTO module (slug, title, description, level_id, type_id, duration_hours, position)
                 SELECT :slug, :title, :desc, l.id, t.id, :duration, :position
                 FROM level l, module_type t
                 WHERE l.slug = :level AND t.slug = :type",
                ['slug' => $slug, 'title' => $title, 'desc' => $desc, 'level' => $level, 'type' => $type, 'duration' => $duration, 'position' => $position]
            );
        }

        // ---- 3. Category assignments ----
        $categoryAssignments = [
            'problem-framing'         => ['ai', 'product'],
            'empathy-storytelling'    => ['leadership', 'product'],
            'ai-foundations'          => ['ai'],
            'prompt-context'          => ['ai'],
            'data-literacy'           => ['ai'],
            'rapid-prototyping'       => ['ai', 'product'],
            'agentic-collaboration'   => ['ai'],
            'ai-coding'               => ['ai'],
            'ai-qa-testing'           => ['ai'],
            'responsible-ai'          => ['ai'],
            'ai-platform-engineering' => ['ai', 'cloud'],
            'ai-security'             => ['ai', 'cloud'],
            'process-reimagination'   => ['ai', 'leadership'],
            'ai-product-design'       => ['ai', 'product'],
        ];
        foreach ($categoryAssignments as $slug => $cats) {
            foreach ($cats as $cat) {
                $this->addSql(
                    "INSERT INTO module_category (module_id, category_id)
                     SELECT m.id, c.id FROM module m, category c
                     WHERE m.slug = :slug AND c.slug = :cat",
                    ['slug' => $slug, 'cat' => $cat]
                );
            }
        }

        // ---- 4. Role assignments ----
        $ALL = ['pm','po','pjm','ba','ux','dev','sm'];
        $roleAssignments = [
            'problem-framing'         => $ALL,
            'empathy-storytelling'    => $ALL,
            'ai-foundations'          => $ALL,
            'prompt-context'          => $ALL,
            'data-literacy'           => $ALL,
            'rapid-prototyping'       => ['pm','po','ux','dev'],
            'agentic-collaboration'   => ['pm','po','pjm','dev'],
            'ai-coding'               => ['dev'],
            'ai-qa-testing'           => ['dev'],
            'responsible-ai'          => ['pm','po','pjm','ba','dev'],
            'ai-platform-engineering' => ['dev'],
            'ai-security'             => ['dev','pjm'],
            'process-reimagination'   => ['pm','po','pjm','ba'],
            'ai-product-design'       => ['pm','po','ux','ba'],
        ];
        foreach ($roleAssignments as $slug => $rls) {
            foreach ($rls as $role) {
                $this->addSql(
                    "INSERT INTO module_role (module_id, role_id)
                     SELECT m.id, r.id FROM module m, role r
                     WHERE m.slug = :slug AND r.slug = :role",
                    ['slug' => $slug, 'role' => $role]
                );
            }
        }

        // ---- 5. Skill assignments (skill slugs are 'ai-<key>') ----
        $skillAssignments = [
            'problem-framing'         => ['identifying-and-framing-problems', 'identifying-and-prioritizing-ai-use-cases', 'designing-ai-centric-products-and-services'],
            'empathy-storytelling'    => ['empathy-and-understanding', 'communication-and-storytelling', 'collaboration-and-facilitation'],
            'ai-foundations'          => ['ai-literacy', 'ai-mechanics-and-technologies', 'ai-tooling-landscape'],
            'prompt-context'          => ['prompting', 'context-management', 'context-and-agent-configuration-engineering'],
            'data-literacy'           => ['data-literacy', 'judgment-and-verification', 'leading-by-example-to-optimize-ai-adoption'],
            'rapid-prototyping'       => ['rapid-prototyping-and-experimentation', 'designing-human-centered-ai-experiences', 'augmenting-everyday-work-with-ai'],
            'agentic-collaboration'   => ['working-with-ai-agents', 'engineering-agentic-systems', 'human-plus-ai-workflows'],
            'ai-coding'               => ['ai-powered-coding', 'ai-centric-way-of-working', 'ai-native-architecture'],
            'ai-qa-testing'           => ['ai-augmented-testing-and-qa', 'ai-quality-and-evaluation', 'ai-quality-evals-and-observability'],
            'responsible-ai'          => ['driving-transparency-and-ethical-ai-usage', 'ensuring-safety-and-compliance', 'judgment-and-verification'],
            'ai-platform-engineering' => ['ai-runtime-and-sandbox-infrastructure', 'ai-in-devops-and-delivery', 'autonomous-delivery-pipelines'],
            'ai-security'             => ['ensuring-safety-and-compliance', 'ai-runtime-and-sandbox-infrastructure', 'ai-quality-evals-and-observability'],
            'process-reimagination'   => ['process-redesign', 'human-plus-ai-workflows', 'operating-model', 'rethink-product-development'],
            'ai-product-design'       => ['designing-ai-centric-products-and-services', 'designing-human-centered-ai-experiences', 'ai-strategy', 'identifying-and-prioritizing-ai-use-cases'],
        ];
        foreach ($skillAssignments as $slug => $skills) {
            foreach ($skills as $key) {
                $this->addSql(
                    "INSERT INTO module_skill (module_id, skill_id)
                     SELECT m.id, sk.id FROM module m, skill sk
                     WHERE m.slug = :slug AND sk.slug = :skill",
                    ['slug' => $slug, 'skill' => 'ai-' . $key]
                );
            }
        }

        // ---- 6. Objectives ----
        $objectives = [
            'problem-framing'         => ['Frame messy situations as testable problem or opportunity statements', 'Separate symptoms, root causes and constraints from solutions', 'Judge which opportunities are worth solving with AI'],
            'empathy-storytelling'    => ['Surface real user and stakeholder needs behind stated wants', 'Structure messages and stories that land with any audience', 'Facilitate productive collaboration across teams'],
            'ai-foundations'          => ['Distinguish rules, machine learning and generative AI', 'Explain how LLMs generate output — including tokens, context and cost', 'Choose the right tool for a task from today’s AI landscape'],
            'prompt-context'          => ['Apply prompting patterns that consistently improve output quality', 'Assemble the right context for a task without waste', 'Design reusable prompts, templates and project setups'],
            'data-literacy'           => ['Read and interrogate a dataset with a critical eye', 'Reason about bias, quality, provenance and consent', 'Judge when data — and a team — are ready for AI'],
            'rapid-prototyping'       => ['Build believable AI prototypes to test the real experience', 'Design flows that handle probabilistic output gracefully', 'Read experiment results and decide what to advance or kill'],
            'agentic-collaboration'   => ['Delegate multi-step work to AI agents with appropriate supervision', 'Split work between humans and agents so the division actually holds', 'Orchestrate multi-agent workflows with cost and safety controls'],
            'ai-coding'               => ['Move confidently between autocomplete, chat and agentic coding', 'Steer agents across multi-file changes and review their output', 'Apply AI-native architecture patterns that respect latency, cost and control'],
            'ai-qa-testing'           => ['Generate meaningful tests and edge cases with AI, not filler', 'Design evaluation suites for probabilistic AI output', 'Instrument tracing, drift and quality monitoring for release gates'],
            'responsible-ai'          => ['Spot bias, fairness and trust risks in AI use before they ship', 'Apply safety, privacy and compliance controls day to day', 'Design lightweight risk and oversight practices for AI features'],
            'ai-platform-engineering' => ['Set up isolated, resource-limited sandboxes for agent execution', 'Operate AI in CI/CD with monitoring, rollback and incident handling', 'Design governed autonomous delivery pipelines with human gates'],
            'ai-security'             => ['Identify AI-specific attack surfaces across models, agents and tools', 'Apply isolation, permissions and secrets controls to AI workloads', 'Red-team AI systems and feed findings into concrete mitigations'],
            'process-reimagination'   => ['Map workflows and find where AI shifts the economics', 'Redesign processes around human + AI collaboration, not tools', 'Evolve the operating model so new ways of working stick'],
            'ai-product-design'       => ['Prioritise AI use-cases on real value and feasibility', 'Write specs that handle probabilistic behaviour and edge cases', 'Design AI experiences that build trust and set honest expectations'],
        ];
        foreach ($objectives as $slug => $texts) {
            foreach ($texts as $pos => $text) {
                $this->addSql(
                    "INSERT INTO module_objective (module_id, text, position)
                     SELECT m.id, :text, :pos FROM module m WHERE m.slug = :slug",
                    ['slug' => $slug, 'text' => $text, 'pos' => $pos]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $newSlugs = "'problem-framing','empathy-storytelling','ai-foundations','prompt-context','data-literacy','rapid-prototyping','agentic-collaboration','ai-coding','ai-qa-testing','responsible-ai','ai-platform-engineering','ai-security','process-reimagination','ai-product-design'";

        $this->addSql("DELETE mo FROM module_objective mo JOIN module m ON mo.module_id = m.id WHERE m.slug IN ($newSlugs)");
        $this->addSql("DELETE ms FROM module_skill ms JOIN module m ON ms.module_id = m.id WHERE m.slug IN ($newSlugs)");
        $this->addSql("DELETE mr FROM module_role mr JOIN module m ON mr.module_id = m.id WHERE m.slug IN ($newSlugs)");
        $this->addSql("DELETE mc FROM module_category mc JOIN module m ON mc.module_id = m.id WHERE m.slug IN ($newSlugs)");
        $this->addSql("DELETE FROM module WHERE slug IN ($newSlugs)");
    }
}
