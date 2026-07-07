<?php

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\Category;
use App\Entity\Level;
use App\Entity\Module;
use App\Entity\ModuleObjective;
use App\Entity\ModuleType;
use App\Entity\Role;
use App\Entity\Session;
use App\Entity\Skill;
use App\Enum\BookingStatus;
use App\Enum\SessionFormat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $levels = $this->createLevels($manager);
        $categories = $this->createCategories($manager);
        $roles = $this->createRoles($manager);
        $types = $this->createModuleTypes($manager);
        $manager->flush();

        $skills = $this->createSkills($manager, $categories);
        $this->createModules($manager, $levels, $categories, $roles, $types, $skills);
        $manager->flush();
    }

    private function createModuleTypes(ObjectManager $manager): array
    {
        $data = [
            ['trainer-led', 'Trainer-led', 1],
            ['self-paced',  'Self-paced',  2],
        ];
        $types = [];
        foreach ($data as [$slug, $name, $pos]) {
            $t = (new ModuleType())->setSlug($slug)->setName($name)->setPosition($pos);
            $manager->persist($t);
            $types[$slug] = $t;
        }
        return $types;
    }

    private function createLevels(ObjectManager $manager): array
    {
        $data = [
            ['foundational', 'Foundational', 1, '#B75EB8', '#FBF5FB', 'Understands the basics and can take part with guidance.'],
            ['competent',    'Competent',    2, '#9A3C9B', '#F8EFF8', 'Applies the skill in everyday situations, with some support.'],
            ['proficient',   'Proficient',   3, '#7C1B7B', '#F4EAF4', 'Works independently in varied, complex situations; analyses and judges.'],
            ['expert',       'Expert',       4, '#561257', '#F0E5F0', 'Sets direction, designs new approaches, and coaches others.'],
        ];
        $levels = [];
        foreach ($data as [$slug, $name, $depth, $color, $tint, $blurb]) {
            $l = (new Level())->setSlug($slug)->setName($name)->setDepth($depth)
                ->setColorHex($color)->setTintHex($tint)->setBlurb($blurb);
            $manager->persist($l);
            $levels[$slug] = $l;
        }
        return $levels;
    }

    private function createCategories(ObjectManager $manager): array
    {
        $data = [
            ['ai',         'AI',         'Artificial Intelligence',    1],
            ['product',    'Product',    'Product Craft',              2],
            ['agile',      'Agile',      'Ways of Working',            3],
            ['cloud',      'Cloud',      'Platform & Infrastructure',  4],
            ['leadership', 'Leadership', 'Digital Leadership',         5],
        ];
        $cats = [];
        foreach ($data as [$slug, $name, $tag, $pos]) {
            $c = (new Category())->setSlug($slug)->setName($name)->setTag($tag)->setPosition($pos);
            $manager->persist($c);
            $cats[$slug] = $c;
        }
        return $cats;
    }

    private function createRoles(ObjectManager $manager): array
    {
        $data = [
            ['pm',  'PM',  'Product Manager',   1],
            ['po',  'PO',  'Product Owner',      2],
            ['pjm', 'PJM', 'Project Manager',    3],
            ['ba',  'BA',  'Business Analyst',   4],
            ['ux',  'UX',  'UX Designer',        5],
            ['dev', 'DEV', 'Developer',          6],
            ['sm',  'SM',  'Scrum Master',       7],
        ];
        $roles = [];
        foreach ($data as [$slug, $code, $name, $pos]) {
            $r = (new Role())->setSlug($slug)->setShortCode($code)->setName($name)->setPosition($pos);
            $manager->persist($r);
            $roles[$slug] = $r;
        }
        return $roles;
    }

    private function createModules(ObjectManager $manager, array $levels, array $cats, array $roles, array $types, array $skills = []): void
    {
        $ALL = ['pm','po','pjm','ba','ux','dev','sm'];
        $SELF_PACED = ['ai-foundations','data-literacy','empathy-storytelling'];

        $moduleSkills = $this->moduleSkills();

        $modules = [
            ['problem-framing', ['ai','product'], 'foundational', 'Problem Framing & Opportunity Discovery', $ALL,
                'Turn ambiguous business challenges into sharply framed problems and validated AI opportunities worth pursuing.',
                ['Frame messy situations as testable problem or opportunity statements','Separate symptoms, root causes and constraints from solutions','Judge which opportunities are worth solving with AI']],

            ['empathy-storytelling', ['leadership','product'], 'foundational', 'Empathy, Communication & Storytelling', $ALL,
                'Build genuine understanding of users and stakeholders, and craft narratives that align people and move work forward.',
                ['Surface real user and stakeholder needs behind stated wants','Structure messages and stories that land with any audience','Facilitate productive collaboration across teams']],

            ['ai-foundations', ['ai'], 'foundational', 'AI Foundations, Models & Tooling', $ALL,
                "A jargon-free grounding in modern AI — how models work, what today's tooling can do, and where it genuinely creates value.",
                ['Distinguish rules, machine learning and generative AI','Explain how LLMs generate output — including tokens, context and cost','Choose the right tool for a task from today’s AI landscape']],

            ['prompt-context', ['ai'], 'competent', 'Prompt Engineering & Context Management', $ALL,
                'Get reliably better results from AI by mastering the two levers that matter most — what you ask, and what you show it.',
                ['Apply prompting patterns that consistently improve output quality','Assemble the right context for a task without waste','Design reusable prompts, templates and project setups']],

            ['data-literacy', ['ai'], 'foundational', 'Data Literacy & AI Readiness', $ALL,
                'How data is collected, shaped and trusted — the raw material every AI initiative quietly depends on.',
                ['Read and interrogate a dataset with a critical eye','Reason about bias, quality, provenance and consent','Judge when data — and a team — are ready for AI']],

            ['rapid-prototyping', ['ai','product'], 'competent', 'Rapid Prototyping, Experimentation & UX Design', ['pm','po','ux','dev'],
                'Shorten the loop from idea to evidence — prototype AI experiences, run honest experiments and design for uncertainty.',
                ['Build believable AI prototypes to test the real experience','Design flows that handle probabilistic output gracefully','Read experiment results and decide what to advance or kill']],

            ['agentic-collaboration', ['ai'], 'proficient', 'Agents, Human–Agent Collaboration & Agentic Teams', ['pm','po','pjm','dev'],
                'Design and lead teams where humans and AI agents share the work — with clear roles, guardrails and trust in the middle.',
                ['Delegate multi-step work to AI agents with appropriate supervision','Split work between humans and agents so the division actually holds','Orchestrate multi-agent workflows with cost and safety controls']],

            ['ai-coding', ['ai'], 'competent', 'AI-Assisted Software Engineering', ['dev'],
                'Ship real code faster and better with autocomplete, chat and agentic coding — from first prompt to production-ready feature.',
                ['Move confidently between autocomplete, chat and agentic coding','Steer agents across multi-file changes and review their output','Apply AI-native architecture patterns that respect latency, cost and control']],

            ['ai-qa-testing', ['ai'], 'competent', 'AI-Powered Quality Assurance & Testing', ['dev'],
                'Use AI to generate tests and drive QA — and build the evals that keep non-deterministic systems honest in production.',
                ['Generate meaningful tests and edge cases with AI, not filler','Design evaluation suites for probabilistic AI output','Instrument tracing, drift and quality monitoring for release gates']],

            ['responsible-ai', ['ai'], 'competent', 'Responsible AI: Ethics, Safety & Compliance', ['pm','po','pjm','ba','dev'],
                'Turn ethics, safety and the EU AI Act from abstract concerns into practical controls teams can actually follow.',
                ['Spot bias, fairness and trust risks in AI use before they ship','Apply safety, privacy and compliance controls day to day','Design lightweight risk and oversight practices for AI features']],

            ['ai-platform-engineering', ['ai','cloud'], 'proficient', 'AI Infrastructure & Platform Engineering', ['dev'],
                'Build the runtime, delivery pipelines and sandboxes that let AI features and agents run safely, cheaply and reliably at scale.',
                ['Set up isolated, resource-limited sandboxes for agent execution','Operate AI in CI/CD with monitoring, rollback and incident handling','Design governed autonomous delivery pipelines with human gates']],

            ['ai-security', ['ai','cloud'], 'proficient', 'AI Security & Secure AI Systems', ['dev','pjm'],
                'Harden AI systems against prompt injection, data leakage and jailbreaks — before attackers get there first.',
                ['Identify AI-specific attack surfaces across models, agents and tools','Apply isolation, permissions and secrets controls to AI workloads','Red-team AI systems and feed findings into concrete mitigations']],

            ['process-reimagination', ['ai','leadership'], 'proficient', 'Business Process Reimagination with AI', ['pm','po','pjm','ba'],
                'Go beyond automating tasks — redesign end-to-end workflows and operating models around human + AI collaboration.',
                ['Map workflows and find where AI shifts the economics','Redesign processes around human + AI collaboration, not tools','Evolve the operating model so new ways of working stick']],

            ['ai-product-design', ['ai','product'], 'competent', 'AI Product Design & Specification', ['pm','po','ux','ba'],
                'Translate fuzzy AI ambitions into product specs that engineers, designers and data scientists can actually build against.',
                ['Prioritise AI use-cases on real value and feasibility','Write specs that handle probabilistic behaviour and edge cases','Design AI experiences that build trust and set honest expectations']],
        ];

        $sessionTemplates = [
            // [month-offset, day, start-hour, start-min, duration-hours, format, location]
            [0, 18, 9, 30, 3.5,  'online',    null],
            [1, 9,  13, 30, 3.5, 'in_person', 'Amsterdam'],
            [1, 22, 9,  30, 7,   'in_person', 'Hilversum'],
            [2, 10, 9,  30, 3.5, 'online',    null],
            [2, 24, 13, 30, 3.5, 'in_person', 'Eindhoven'],
            [3, 8,  9,  30, 7,   'in_person', 'Amsterdam'],
            [3, 21, 9,  30, 3.5, 'online',    null],
        ];

        $baseDate = new \DateTimeImmutable('2026-06-01');

        foreach ($modules as $pos => [$id, $catSlugs, $levelSlug, $title, $roleIds, $desc, $objTexts]) {
            $module = (new Module())
                ->setTitle($title)
                ->setSlug($id)
                ->setDescription($desc)
                ->setLevel($levels[$levelSlug])
                ->setType(in_array($id, $SELF_PACED, true) ? $types['self-paced'] : $types['trainer-led'])
                ->setPosition($pos);

            foreach ((array) $catSlugs as $catSlug) {
                if (isset($cats[$catSlug])) {
                    $module->addCategory($cats[$catSlug]);
                }
            }
            foreach ($roleIds as $rid) {
                $module->addRole($roles[$rid]);
            }

            foreach ($moduleSkills[$id] ?? [] as $skillKey) {
                if (isset($skills[$skillKey])) {
                    $module->addSkill($skills[$skillKey]);
                }
            }

            foreach ($objTexts as $objPos => $text) {
                $obj = (new ModuleObjective())->setText($text)->setPosition($objPos);
                $module->addObjective($obj);
                $manager->persist($obj);
            }

            // Generate 2-3 sessions using a hash for determinism
            $hash = crc32($id);
            $count = 2 + (($hash & 0x7FFFFFFF) % 2);
            $usedOffsets = [];

            for ($i = 0; $i < $count; $i++) {
                $tplIdx = (($hash >> ($i * 3)) & 0x7FFFFFFF) % count($sessionTemplates);
                // avoid exact duplicate offsets
                while (in_array($tplIdx, $usedOffsets)) {
                    $tplIdx = ($tplIdx + 1) % count($sessionTemplates);
                }
                $usedOffsets[] = $tplIdx;

                [$monthOffset, $day, $startH, $startM, $durationH, $fmt, $location] = $sessionTemplates[$tplIdx];

                $startDate = $baseDate->modify("+{$monthOffset} months")->setDate(
                    (int)$baseDate->modify("+{$monthOffset} months")->format('Y'),
                    (int)$baseDate->modify("+{$monthOffset} months")->format('m'),
                    $day
                );
                $startsAt = $startDate->setTime($startH, $startM);
                $durationMins = (int)($durationH * 60);
                $endsAt = $startsAt->modify("+{$durationMins} minutes");

                $seatHash = ($hash >> ($i * 7)) & 0x7FFFFFFF;
                $variant = $seatHash % 7;
                if ($variant === 0) {
                    $activeBookings = 12;
                } elseif ($variant <= 2) {
                    $activeBookings = 10 + ($seatHash % 2);
                } else {
                    $activeBookings = 4 + ($seatHash % 6);
                }

                $session = (new Session())
                    ->setStartsAt($startsAt)
                    ->setEndsAt($endsAt)
                    ->setFormat($fmt === 'online' ? SessionFormat::Online : SessionFormat::InPerson)
                    ->setLocation($location)
                    ->setCapacity(12);

                // seed fake bookings so seatsLeft is realistic
                for ($b = 0; $b < $activeBookings; $b++) {
                    $booking = (new Booking())
                        ->setGuestName('Seat Holder ' . $b)
                        ->setGuestEmail("holder{$b}@example.com")
                        ->setGuestPhone('0600000000')
                        ->setStatus(BookingStatus::Booked);
                    $session->addBooking($booking);
                    $manager->persist($booking);
                }

                $module->addSession($session);
                $manager->persist($session);
            }

            $manager->persist($module);
        }
    }

    private function createSkills(ObjectManager $manager, array $cats): array
    {
        $HUMAN_CORE     = ['human-core',      'Durable Human Core'];
        $WORKING_AI     = ['working-with-ai', 'Working With AI'];
        $LEADING_AI     = ['leading-with-ai', 'Leading With AI'];
        $ENABLER        = ['enabler',         'Enabler Ring'];

        // [slug, name, [ringSlug, ringName], [domainSlug, domainName], capabilityKey, viewScope, [foundation, practitioner, professional, expert]]
        $skills = [
            // ---------- Ring 1: Durable Human Core ----------
            ['identifying-and-framing-problems', 'Identifying & Framing Problems & Opportunities', $HUMAN_CORE, ['critical-thinking', 'Critical Thinking'], 'critical-thinking', 'common', [
                'Understands the difference between a problem, a symptom and a solution, and why framing problems and opportunities matters.',
                'Uses a framing technique to articulate a clear problem or opportunity statement for a given situation.',
                'Breaks messy situations into root causes, assumptions and constraints; spots opportunities; and judges what is worth solving — and whether AI is the right route.',
                'Formulates original, well-scoped problem and opportunity statements that reframe challenges and open new solution spaces, and coaches others.',
            ]],
            ['judgment-and-verification', 'Judgment & Verification', $HUMAN_CORE, ['critical-thinking', 'Critical Thinking'], 'critical-thinking', 'common', [
                'Understands that AI output can be wrong, biased or fabricated; that a human stays accountable for it; and that over-relying on AI can erode one\'s own skills.',
                'Applies a verification routine, keeps a human in the loop, and stays actively engaged rather than rubber-stamping AI output.',
                'Pinpoints errors, bias and risk; judges when to accept, verify, override — or not use AI at all; and balances AI use against maintaining their own judgment and expertise.',
                'Designs verification, human-oversight and healthy-reliance practices (accountability, when-not-to-use guidance, avoiding skill atrophy) that keep AI use reliable across a team.',
            ]],
            ['learning-agility', 'Learning Agility', $HUMAN_CORE, ['critical-thinking', 'Critical Thinking'], 'critical-thinking', 'common', [
                'Understands that AI tools and practices change fast and that staying current matters.',
                'Picks up a new AI tool or technique and uses it in real work.',
                'Compares new and old approaches and judges what to adopt, adapt or drop — separating signal from hype.',
                'Builds a personal learning system that stays current and helps others learn too.',
            ]],
            ['empathy-and-understanding', 'Empathy & Understanding', $HUMAN_CORE, ['human-centricity', 'Human-Centricity'], 'human-centricity', 'common', [
                'Understands who the users and stakeholders are and their stated needs.',
                'Uses interviews, observation and active listening to surface real needs.',
                'Distinguishes stated wants from underlying needs, detects unspoken concerns, and judges whose perspective should drive a decision.',
                'Synthesizes diverse perspectives into a shared understanding that guides what to build.',
            ]],
            ['communication-and-storytelling', 'Communication & Storytelling', $HUMAN_CORE, ['human-centricity', 'Human-Centricity'], 'human-centricity', 'common', [
                'Understands what makes a message and a story clear.',
                'Structures and delivers a clear message or story to an audience.',
                'Tailors a narrative to the audience and context, and judges whether it will land and persuade.',
                'Crafts original, compelling narratives that align people and drive action.',
            ]],
            ['collaboration-and-facilitation', 'Collaboration & Facilitation', $HUMAN_CORE, ['human-centricity', 'Human-Centricity'], 'human-centricity', 'common', [
                'Understands collaboration norms and basic facilitation methods.',
                'Applies facilitation techniques to run a productive working session.',
                'Reads group dynamics and judges which collaboration approach (including human + AI) fits a situation.',
                'Designs collaborative ways of working that bring people and AI together effectively.',
            ]],

            // ---------- Ring 2: Working With AI — AI Fluency ----------
            ['ai-literacy', 'AI Literacy', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'common', [
                'Explains in plain terms what AI can and cannot do, and where it is appropriate, risky or unsuitable.',
                'Chooses appropriate tasks for AI in everyday work.',
                'Analyses a task for AI-fit and judges the appropriateness and risk of using AI for a given purpose.',
                'Shapes sensible guidance for when and how a team should (and shouldn\'t) use AI.',
            ]],
            ['ai-mechanics-and-technologies', 'AI Mechanics & Technologies', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'common', [
                'Describes at a working level how LLMs generate output and key concepts (tokens, context windows, training), why they hallucinate, and that AI is not free — usage consumes tokens and is increasingly billed by usage.',
                'Uses knowledge of how AI works — including token and cost awareness — to get better, more reliable and more cost-efficient results.',
                'Reasons about why an AI produced a given output, and judges which AI approach or model suits a need, weighing capability against token and usage cost.',
                'Explains AI mechanics — including the economics of token usage — to others, and designs simple mental models that make it accessible.',
            ]],
            ['ai-tooling-landscape', 'AI Tooling Landscape', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'common', [
                'Names the major AI tools and what each is for.',
                'Uses the appropriate tool and surface for a common task.',
                'Compares tools across capability, cost, privacy and fit, and selects the right one with justification.',
                'Assembles a personal or team AI toolkit with recommendations for different needs.',
            ]],
            ['prompting', 'Prompting', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'common', [
                'Understands the basic elements of a prompt and why structure improves output.',
                'Applies prompting techniques to get useful results, and refines when they fall short.',
                'Diagnoses why a prompt underperformed and judges between alternative prompting strategies.',
                'Designs reusable prompts, templates and patterns for themselves and others.',
            ]],
            ['context-management', 'Context Management', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'generic', [
                'Understands that the information given to an AI shapes its output, and that too little or too much context affects both quality and cost.',
                'Provides the right context (documents, examples, constraints) for a task.',
                'Analyses what context is relevant versus noise, and judges how much to include to balance relevance, creativity and token/usage cost.',
                'Designs context setups (projects, knowledge, reusable briefs) that consistently improve output.',
            ]],
            ['context-and-agent-configuration-engineering', 'Context & Agent Configuration Engineering', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'eng', [
                'Understands that project memory, rules and prompt files shape AI coding results, and can follow an existing setup (CLAUDE.md, AGENTS.md, Cursor rules).',
                'Authors and maintains project memory, rules and reusable prompts for their own work, improving the reliability of AI tools.',
                'Designs custom skills, commands and context/harness setups for a codebase, and tunes them from observed agent behaviour.',
                'Establishes context-and-configuration standards (memory, rules, skills, harnesses) the whole team builds on, and evolves them as tools change.',
            ]],
            ['data-literacy', 'Data Literacy', $WORKING_AI, ['ai-fluency', 'AI Fluency'], 'ai-fluency', 'common', [
                'Understands basic data terms, charts and metrics and what they show.',
                'Uses data to answer a question or support a decision.',
                'Analyses data for patterns and pitfalls (e.g., correlation vs causation), and judges whether it supports a claim or is fit for an AI use.',
                'Designs a simple measurement or experiment to generate the evidence needed.',
            ]],

            // ---------- Ring 2: Working With AI — AI-Powered Work ----------
            ['augmenting-everyday-work-with-ai', 'Augmenting Everyday Work with AI', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'generic', [
                'Understands that AI can speed up and improve everyday knowledge work, and recognizes which of their own tasks it suits.',
                'Uses AI to do real recurring tasks — drafting, summarizing, researching and synthesizing, analyzing, preparing materials, planning — with good results.',
                'Analyses their own workflow to apply AI where it adds most value, and judges output quality before relying on it — without over-relying or letting their own skills atrophy.',
                'Designs AI-augmented personal and team working routines, and shares reusable approaches that lift everyone\'s everyday productivity.',
            ]],
            ['ai-powered-coding', 'AI-Powered Coding', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'eng', [
                'Understands what AI coding tools do (autocomplete, chat, agentic coding) and where they help or mislead; has tried them on real code.',
                'Uses autocomplete, chat and in-IDE agentic coding to write and change code day to day, and reviews AI output before committing.',
                'Completes features across multiple files with agentic coding — steering and correcting the agent — and runs AI-assisted code review and test generation as routine practice.',
                'Ships substantial, cross-system work primarily through agentic coding; sets the team\'s coding-with-AI patterns, guardrails and review standards.',
            ]],
            ['rapid-prototyping-and-experimentation', 'Rapid Prototyping & Experimentation', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'common', [
                'Understands how AI-assisted prototyping shortens the idea-to-evidence loop, for any kind of output (not just software).',
                'Builds a simple prototype with AI tools — a page, copy, a visual, a model or a feature — to make an idea tangible.',
                'Analyses prototype and experiment results, and judges whether the evidence supports advancing or killing an idea.',
                'Designs and builds prototypes and experiments that validate ideas quickly.',
            ]],
            ['ai-centric-way-of-working', 'AI-Centric Way-of-Working', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'generic', [
                'Understands how AI changes architecture, development, testing and delivery.',
                'Applies AI tools to their part of the lifecycle in standard cases.',
                'Analyses where AI helps or hurts quality and speed, and judges when to rely on AI versus keep human control.',
                'Designs an AI-native way of working for a team across the whole lifecycle.',
            ]],
            ['ai-native-architecture', 'AI-Native Architecture', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'eng', [
                'Understands how AI/agentic, non-deterministic systems differ from deterministic software (latency, cost, drift, autonomy vs. control).',
                'Applies AI-native patterns to a component — choosing where AI fits, how data flows, and where humans stay in control.',
                'Designs the architecture for an AI feature or service, reasoning explicitly about cost, latency, reliability, safety and oversight trade-offs.',
                'Sets AI-native architectural direction across systems — multi-agent design, integration, and capability-vs-safety trade-offs — and guides others.',
            ]],
            ['ai-augmented-testing-and-qa', 'AI-Augmented Testing & QA', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'eng', [
                'Understands how AI can generate tests and drive browser/QA testing, and the limits of AI-generated tests.',
                'Uses AI to generate tests and surface edge cases for their own code, checking the tests are meaningful.',
                'Builds AI-augmented test suites (incl. browser-testing agents) into the workflow, and judges coverage and quality.',
                'Designs the team\'s AI-augmented testing/QA approach — balancing speed with genuine assurance — and sets quality gates.',
            ]],
            ['ai-in-devops-and-delivery', 'AI in DevOps & Delivery (LLMOps)', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'eng', [
                'Understands what it takes to run AI/agents in production (deployment, monitoring, rollback) and how it differs from classic ops.',
                'Uses AI in CI/CD and delivery tasks, and helps operate AI features with basic monitoring.',
                'Builds AI into delivery pipelines and runs AI/agents in production with monitoring, rollback and incident handling.',
                'Designs the org\'s LLMOps approach — deployment, observability, cost/SLA management and incident response for agents at scale.',
            ]],
            ['working-with-ai-agents', 'Working With AI Agents', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'generic', [
                'Understands what an AI agent is, what agents can and can\'t do, the risks of autonomy, and that agents can consume tokens and usage cost quickly.',
                'Directs and delegates a multi-step task to an AI agent and supervises the result.',
                'Breaks a goal into agent-suitable steps; judges when to trust an agent to act, when to stay in the loop, and whether its output is sound; and keeps token and usage cost under control.',
                'Designs and orchestrates multi-agent or tool-using setups that get reliable work done with appropriate human control.',
            ]],
            ['engineering-agentic-systems', 'Engineering Agentic Systems', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'eng', [
                'Understands what agents, MCP tools and subagents are, and the risks of letting them act; has run a multi-step agent task.',
                'Wires up MCP servers / tool integrations and uses subagents or background agents for real tasks, supervising results and cost.',
                'Designs multi-agent or tool-using workflows with clear roles, guardrails and cost control, and debugs them when they misbehave.',
                'Architects reliable agentic systems (multi-agent orchestration, async/background agents) others depend on, with guardrails, evals and cost controls built in.',
            ]],
            ['ai-quality-and-evaluation', 'AI Quality & Evaluation', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'generic', [
                'Understands why AI\'s non-deterministic output needs deliberate, repeatable quality checks (not one-off testing).',
                'Applies an existing evaluation or test to check AI output against criteria.',
                'Analyses evaluation results to locate quality issues, and judges whether a system is good enough to ship against defined criteria.',
                'Designs evaluation systems ("evals") that measure and improve AI quality over time.',
            ]],
            ['ai-quality-evals-and-observability', 'AI Quality, Evals & Observability', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'eng', [
                'Understands why non-deterministic systems need evals and observability, not one-off tests.',
                'Applies existing evals and reads tracing/monitoring to check AI behaviour.',
                'Designs eval suites and instruments tracing and drift/cost/jailbreak monitoring for an AI system, and judges release-readiness.',
                'Builds production-grade eval + observability that gate releases and continuously improve quality, and sets the standard for the team.',
            ]],
            ['designing-human-centered-ai-experiences', 'Designing Human-Centered AI Experiences', $WORKING_AI, ['ai-powered-work', 'AI-Powered Work'], 'ai-powered-work', 'common', [
                'Understands the patterns that make an AI experience trustworthy and usable (trust cues, control, error states).',
                'Applies known patterns to design a basic AI interaction.',
                'Analyses an AI experience for trust, control and transparency gaps, and judges whether it keeps users informed, in control and safe.',
                'Designs original AI experiences that build trust, handle uncertainty, and serve both humans and AI agents (including discoverability by AI).',
            ]],

            // ---------- Ring 2: Working With AI — Frontier Software Engineering (eng-only) ----------
            ['ai-runtime-and-sandbox-infrastructure', 'AI Runtime & Sandbox Infrastructure', $WORKING_AI, ['frontier-engineering', 'Frontier Software Engineering'], 'ai-powered-work', 'eng', [
                'Understands why agents need isolated, safe runtimes and what sandboxing protects against.',
                'Runs agents in an existing sandbox/runtime (E2B, Modal, containers) for their own tasks.',
                'Sets up sandboxed execution environments with appropriate isolation, permissions and resource limits for agent work.',
                'Builds and operates the org\'s agent-runtime platform — secure, scalable, isolated execution that others rely on.',
            ]],
            ['autonomous-delivery-pipelines', 'Autonomous Delivery Pipelines (Dark Factory)', $WORKING_AI, ['frontier-engineering', 'Frontier Software Engineering'], 'ai-powered-work', 'eng', [
                'Understands the spec-to-PR / "dark factory" concept and where autonomy is (and isn\'t) appropriate.',
                'Runs parts of delivery autonomously (e.g., agent-generated PRs) with human review at the gate.',
                'Designs a governed autonomous pipeline for a workflow — agents build and review, humans approve at defined gates — with evals and rollback.',
                'Operates a governed autonomous (spec-to-PR) pipeline in production, with oversight, safety and quality controls — the L5 frontier.',
            ]],

            // ---------- Ring 3: Leading With AI — AI Strategizing ----------
            ['ai-strategy', 'AI Strategy', $LEADING_AI, ['ai-strategizing', 'AI Strategizing'], 'ai-strategizing', 'common', [
                'Understands how AI can create value and connect to business outcomes.',
                'Applies a strategy frame to articulate where AI should focus in their area.',
                'Analyses the business to find where AI creates most value, and judges and prioritizes competing directions.',
                'Formulates a focused, coherent AI strategy and set of choices.',
            ]],
            ['identifying-and-prioritizing-ai-use-cases', 'Identifying & Prioritizing AI Use-Cases', $LEADING_AI, ['ai-strategizing', 'AI Strategizing'], 'ai-strategizing', 'common', [
                'Understands how to assess a use-case on value and feasibility.',
                'Applies a scoring approach to a set of candidate use-cases.',
                'Analyses use-cases for value, risk, effort and dependencies, and prioritizes what to pursue, scale or stop.',
                'Builds a prioritized use-case portfolio tied to outcomes.',
            ]],
            ['designing-ai-centric-products-and-services', 'Designing AI-Centric Products & Services', $LEADING_AI, ['ai-strategizing', 'AI Strategizing'], 'ai-strategizing', 'common', [
                'Understands how AI can change what an offering does and the value it creates.',
                'Applies AI ideas to enhance an existing offering.',
                'Analyses where value migrates and what AI makes newly possible, and judges the viability of a value proposition.',
                'Designs a reimagined, AI-centric product or service offering.',
            ]],
            ['ai-value-optimization', 'AI Value Optimization', $LEADING_AI, ['ai-strategizing', 'AI Strategizing'], 'ai-strategizing', 'common', [
                'Understands how to define and measure the value of an AI initiative, including its usage/token cost base.',
                'Applies metrics to track an AI initiative\'s value.',
                'Analyses results to see where value is and isn\'t realized, and judges whether to continue or scale.',
                'Designs a value-measurement and optimization approach for AI initiatives.',
            ]],

            // ---------- Ring 3: Leading With AI — Transforming Work ----------
            ['process-redesign', 'Process Redesign', $LEADING_AI, ['transforming-work', 'Transforming Work'], 'transforming-work', 'common', [
                'Understands the difference between automating a task and redesigning a process.',
                'Maps a current workflow and identifies AI opportunities.',
                'Analyses a workflow for waste, bottlenecks and AI-suitable steps, and judges which redesign delivers the most improvement.',
                'Redesigns an end-to-end workflow around human + AI collaboration.',
            ]],
            ['human-plus-ai-workflows', 'Human + AI Workflows', $LEADING_AI, ['transforming-work', 'Transforming Work'], 'transforming-work', 'common', [
                'Understands where humans should stay in the loop and why.',
                'Applies a human-in-the-loop pattern to a simple workflow.',
                'Analyses a task to decide which parts AI executes and which humans own, and judges whether the division is effective, safe and trusted.',
                'Designs human + AI workflows with clear roles, hand-offs and oversight.',
            ]],
            ['rethink-product-development', 'Rethink Product Development', $LEADING_AI, ['transforming-work', 'Transforming Work'], 'transforming-work', 'common', [
                'Understands how AI changes the cost and speed of building, and challenges backlogs, specs and prioritization.',
                'Applies AI to part of the product-development process (e.g., faster prototyping).',
                'Analyses where current product practices break down with AI, and judges what to keep, change or drop.',
                'Designs a rethought product-development approach for AI-enabled teams.',
            ]],
            ['operating-model', 'Operating Model', $LEADING_AI, ['transforming-work', 'Transforming Work'], 'transforming-work', 'common', [
                'Understands how AI and agents change roles and team structures.',
                'Applies role and structure changes for AI in a small scope.',
                'Analyses where the current operating model blocks AI value, and judges operating-model options.',
                'Designs a human + agent operating model that sustains AI ways of working.',
            ]],

            // ---------- Ring 4: Enabler Ring — Cross-Cutting ----------
            ['driving-transparency-and-ethical-ai-usage', 'Driving Transparency & Ethical AI Usage', $ENABLER, ['cross-cutting', 'Cross-Cutting Capabilities'], 'cross-cutting', 'common', [
                'Understands common AI ethical risks and sources of bias, and what transparency and human oversight mean for AI.',
                'Applies basic checks to reduce bias, and applies transparency and oversight practices in their AI use.',
                'Analyses an AI use for fairness, bias and where trust is gained or lost, and judges whether it is ethical, transparent and accountable enough.',
                'Establishes practices that embed ethics, reduce bias by design, and earn and sustain trust across a team.',
            ]],
            ['ensuring-safety-and-compliance', 'Ensuring Safety & Compliance Through Responsible Use of AI', $ENABLER, ['cross-cutting', 'Cross-Cutting Capabilities'], 'cross-cutting', 'common', [
                'Understands key AI-specific risks (prompt injection, data leakage), privacy concerns, and that rules apply (e.g., the EU AI Act).',
                'Applies safe-use and required-control practices — handling data and secrets correctly, using sanctioned tools — and knows when to escalate.',
                'Analyses an AI use or feature for security, privacy, risk and compliance gaps, and judges whether it is safe and compliant enough.',
                'Designs mitigations, safe-use practices and lightweight risk/compliance routines for AI features, tools and teams.',
            ]],
            ['leading-by-example-to-optimize-ai-adoption', 'Leading by Example to Optimize AI Adoption & Organizational Readiness', $ENABLER, ['cross-cutting', 'Cross-Cutting Capabilities'], 'cross-cutting', 'common', [
                'Understands why access to AI doesn\'t equal adoption, what makes a team ready, and why visible, responsible role-modelling matters.',
                'Uses AI openly and responsibly as an example, and applies adoption and readiness practices in their team.',
                'Analyses where and why adoption stalls and where a team\'s readiness gaps are, and judges which interventions and behaviours will most improve adoption.',
                'Designs and leads an adoption effort — role-modelling, readiness-building and coaching — that turns access into sustained value across the organization.',
            ]],
        ];

        $aiCat = $cats['ai'];
        $map = [];
        foreach ($skills as $pos => [$slug, $name, $ring, $domain, $capKey, $view, $descs]) {
            [$ringSlug, $ringName] = $ring;
            [$domainSlug, $domainName] = $domain;

            $skill = (new Skill())
                ->setSlug('ai-' . $slug)
                ->setName($name)
                ->setCategory($aiCat)
                ->setCapabilityKey($capKey)
                ->setDomainSlug($domainSlug)
                ->setDomainName($domainName)
                ->setRingSlug($ringSlug)
                ->setRingName($ringName)
                ->setViewScope($view)
                ->setPosition($pos)
                ->setDescriptions([
                    'foundational'   => $descs[0],
                    'competent' => $descs[1],
                    'proficient' => $descs[2],
                    'expert'       => $descs[3],
                ]);
            $manager->persist($skill);
            $map[$slug] = $skill;
        }
        return $map;
    }

    /**
     * Maps module slugs to the skill keys they grow (short slug, without the 'ai-' prefix).
     */
    private function moduleSkills(): array
    {
        return [
            'problem-framing' => ['identifying-and-framing-problems', 'identifying-and-prioritizing-ai-use-cases', 'designing-ai-centric-products-and-services'],
            'empathy-storytelling' => ['empathy-and-understanding', 'communication-and-storytelling', 'collaboration-and-facilitation'],
            'ai-foundations' => ['ai-literacy', 'ai-mechanics-and-technologies', 'ai-tooling-landscape'],
            'prompt-context' => ['prompting', 'context-management', 'context-and-agent-configuration-engineering'],
            'data-literacy' => ['data-literacy', 'judgment-and-verification', 'leading-by-example-to-optimize-ai-adoption'],
            'rapid-prototyping' => ['rapid-prototyping-and-experimentation', 'designing-human-centered-ai-experiences', 'augmenting-everyday-work-with-ai'],
            'agentic-collaboration' => ['working-with-ai-agents', 'engineering-agentic-systems', 'human-plus-ai-workflows'],
            'ai-coding' => ['ai-powered-coding', 'ai-centric-way-of-working', 'ai-native-architecture'],
            'ai-qa-testing' => ['ai-augmented-testing-and-qa', 'ai-quality-and-evaluation', 'ai-quality-evals-and-observability'],
            'responsible-ai' => ['driving-transparency-and-ethical-ai-usage', 'ensuring-safety-and-compliance', 'judgment-and-verification'],
            'ai-platform-engineering' => ['ai-runtime-and-sandbox-infrastructure', 'ai-in-devops-and-delivery', 'autonomous-delivery-pipelines'],
            'ai-security' => ['ensuring-safety-and-compliance', 'ai-runtime-and-sandbox-infrastructure', 'ai-quality-evals-and-observability'],
            'process-reimagination' => ['process-redesign', 'human-plus-ai-workflows', 'operating-model', 'rethink-product-development'],
            'ai-product-design' => ['designing-ai-centric-products-and-services', 'designing-human-centered-ai-experiences', 'ai-strategy', 'identifying-and-prioritizing-ai-use-cases'],
        ];
    }
}
