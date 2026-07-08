<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Restore the non-AI modules (product / agile / cloud / leadership) that were
 * wiped by Version20260707170000. The 14 new AI-focused modules stay; old AI
 * modules (ai-f-*, ai-i-*, ai-a-*, ai-e-*) are intentionally NOT restored.
 *
 * Idempotent: INSERT IGNORE on module (slug unique), then child rows are
 * added only for modules whose id we can look up by slug.
 */
final class Version20260708100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore non-AI modules (product/agile/cloud/leadership) with categories, roles, and objectives.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $modules = [
            [
                'slug' => 'pr-f-1',
                'title' => 'Product Thinking Fundamentals',
                'desc' => 'The mindset shift from building features to solving real problems for real people.',
                'level' => 'foundational',
                'self_paced' => true,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Frame work as problems, not features',
                    'Connect work to user and business value',
                    'Adopt an outcome-first mindset',
                ],
            ],
            [
                'slug' => 'pr-f-2',
                'title' => 'Discovery vs Delivery',
                'desc' => 'How great teams continuously discover what to build while reliably delivering what they have decided.',
                'level' => 'foundational',
                'self_paced' => true,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Separate discovery from delivery',
                    'Run lightweight discovery',
                    'Reduce waste and rework',
                ],
            ],
            [
                'slug' => 'pr-i-1',
                'title' => 'Outcome-Based Roadmapping',
                'desc' => 'Build roadmaps around outcomes and bets instead of fixed feature lists and dates.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po'],
                'objs' => [
                    'Frame roadmaps around outcomes',
                    'Communicate uncertainty honestly',
                    'Align stakeholders on bets',
                ],
            ],
            [
                'slug' => 'pr-i-2',
                'title' => 'Customer Interviewing',
                'desc' => 'Run interviews that surface real needs and behaviours — not the answers you hoped to hear.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ux', 'ba'],
                'objs' => [
                    'Recruit and structure interviews',
                    'Ask non-leading questions',
                    'Synthesise signal from noise',
                ],
            ],
            [
                'slug' => 'pr-i-3',
                'title' => 'Prioritisation Frameworks',
                'desc' => 'Practical frameworks for deciding what to do next when everything feels important.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'pjm'],
                'objs' => [
                    'Apply value-vs-effort thinking',
                    'Use frameworks without dogma',
                    'Make and defend trade-offs',
                ],
            ],
            [
                'slug' => 'pr-a-1',
                'title' => 'Product Strategy & Vision',
                'desc' => 'Craft a vision and strategy that gives teams a clear, motivating direction to make decisions against.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po'],
                'objs' => [
                    'Articulate a compelling vision',
                    'Translate strategy into focus',
                    'Cascade strategy to teams',
                ],
            ],
            [
                'slug' => 'pr-a-2',
                'title' => 'Experimentation & A/B Testing',
                'desc' => 'Design and read experiments that actually tell you whether a change moved the needle.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'dev'],
                'objs' => [
                    'Design sound experiments',
                    'Reason about significance',
                    'Avoid common testing traps',
                ],
            ],
            [
                'slug' => 'pr-e-1',
                'title' => 'Product Operating Model',
                'desc' => 'Shape how product, design and engineering work together to deliver value at scale.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'pjm'],
                'objs' => [
                    'Design an operating model',
                    'Balance autonomy and alignment',
                    'Evolve the model over time',
                ],
            ],
            [
                'slug' => 'ag-f-1',
                'title' => 'Agile Mindset & Values',
                'desc' => 'The principles behind agile ways of working and why they change how teams decide and deliver.',
                'level' => 'foundational',
                'self_paced' => true,
                'cats' => ['agile'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Internalise the agile values',
                    'Recognise agile vs cargo-cult agile',
                    'Apply an empirical mindset',
                ],
            ],
            [
                'slug' => 'ag-f-2',
                'title' => 'Scrum in Practice',
                'desc' => 'Roles, events and artefacts of Scrum, grounded in how real teams run them day to day.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Run the core Scrum events well',
                    'Clarify roles and accountabilities',
                    'Avoid common Scrum anti-patterns',
                ],
            ],
            [
                'slug' => 'ag-i-1',
                'title' => 'Backlog Refinement',
                'desc' => 'Keep a backlog that is ready, valuable and just-enough — without drowning in detail.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['po', 'pm'],
                'objs' => [
                    'Slice and order work by value',
                    'Keep items ready for delivery',
                    'Balance discovery and delivery',
                ],
            ],
            [
                'slug' => 'ag-i-2',
                'title' => 'Facilitating Agile Ceremonies',
                'desc' => 'Facilitation techniques that make planning, reviews and retros genuinely useful.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['pjm'],
                'objs' => [
                    'Design and run focused ceremonies',
                    'Surface conflict productively',
                    'Drive clear decisions and actions',
                ],
            ],
            [
                'slug' => 'ag-i-3',
                'title' => 'User Story Mapping',
                'desc' => 'Build a shared, visual model of the user journey to plan releases around real outcomes.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['pm', 'po', 'ux'],
                'objs' => [
                    'Map journeys to a release plan',
                    'Find gaps and slice thin releases',
                    'Align the team on outcomes',
                ],
            ],
            [
                'slug' => 'ag-a-1',
                'title' => 'Scaling Agile (SAFe / LeSS)',
                'desc' => 'Coordinate many teams toward one product without recreating the bureaucracy agile replaced.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['pjm', 'pm'],
                'objs' => [
                    'Compare scaling frameworks honestly',
                    'Coordinate cross-team dependencies',
                    'Keep flow at scale',
                ],
            ],
            [
                'slug' => 'ag-a-2',
                'title' => 'Agile Metrics & Flow',
                'desc' => 'Measure flow and outcomes — not velocity theatre — to improve how work actually moves.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['pm', 'pjm'],
                'objs' => [
                    'Read flow and lead-time metrics',
                    'Spot and remove bottlenecks',
                    'Improve predictability',
                ],
            ],
            [
                'slug' => 'ag-e-1',
                'title' => 'Enterprise Agile Coaching',
                'desc' => 'Coach leaders and teams through deep, organisation-wide changes in how work happens.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['pjm'],
                'objs' => [
                    'Coach at team and org level',
                    'Navigate resistance and politics',
                    'Sustain change over time',
                ],
            ],
            [
                'slug' => 'sm-f-1',
                'title' => 'Foster Psychological Safety',
                'desc' => 'The everyday practices that make it safe to speak up, disagree and ask for help — the prerequisite for any real team performance.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'po', 'pjm'],
                'objs' => [
                    'Build a toolkit for creating safety in teams',
                    'Recognise and address fear and anxiety at work',
                    'Establish norms that enable honest and open communication',
                ],
            ],
            [
                'slug' => 'sm-f-2',
                'title' => 'Give and Receive Feedback',
                'desc' => 'How to give feedback that actually changes behaviour, and how to receive it without flinching.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'po', 'pjm'],
                'objs' => [
                    'Deliver constructive feedback that actually lands',
                    'Receive feedback without defensiveness',
                    'Establish a peer feedback rhythm in your team',
                ],
            ],
            [
                'slug' => 'sm-f-3',
                'title' => 'Teach Agile Values & Principles',
                'desc' => 'Beyond ceremonies — the values and principles behind agile, and how to explain them so they stick.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm', 'pm'],
                'objs' => [
                    'Explain the Agile Manifesto in your own words',
                    'Connect values to everyday team decisions',
                    'Distinguish agile mindset from agile process',
                ],
            ],
            [
                'slug' => 'sm-f-4',
                'title' => 'Teach Scrum Foundations',
                'desc' => 'Scrum\'s accountabilities, events and artifacts taught through play and experience, not slides.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'po', 'dev'],
                'objs' => [
                    'Train others in Scrum\'s accountabilities events and artifacts',
                    'Apply empiricism to inspect and adapt',
                    'Run a Scrum cycle experientially using games and simulation',
                ],
            ],
            [
                'slug' => 'sm-f-5',
                'title' => 'Ask Powerful Questions',
                'desc' => 'The coaching habit of holding back advice and asking the question that opens new thinking.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm', 'pm'],
                'objs' => [
                    'Listen deeply without jumping to solutions',
                    'Ask questions that open thinking rather than close it',
                    'Distinguish a coaching conversation from advice-giving',
                ],
            ],
            [
                'slug' => 'sm-f-6',
                'title' => 'Design Playful Learning Experiences',
                'desc' => 'Use games, simulations and physical play to make abstract agile ideas tangible and memorable.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm'],
                'objs' => [
                    'Use games and simulations to make abstract concepts tangible',
                    'Create safety and energy through play',
                    'Design learning activities grounded in neuroscience',
                ],
            ],
            [
                'slug' => 'sm-i-1',
                'title' => 'Facilitate Scrum Events',
                'desc' => 'Run Planning, Daily, Review and Retrospective so they stay purposeful, honest and time-boxed.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'po'],
                'objs' => [
                    'Facilitate Planning Daily Review and Retrospective with skill',
                    'Keep events purposeful time-boxed and engaging',
                    'Foster honest inspection and psychological safety in every event',
                ],
            ],
            [
                'slug' => 'sm-i-2',
                'title' => 'Bring Scrum Values to Life',
                'desc' => 'Move Scrum\'s five values from posters on the wall into the everyday behaviour of the team.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'po', 'dev'],
                'objs' => [
                    'Recognise when values are lived or violated in practice',
                    'Roleplay and debrief challenging value-conflict scenarios',
                    'Design plays that embed values into daily team behaviour',
                ],
            ],
            [
                'slug' => 'sm-i-3',
                'title' => 'Manage Visualise and Measure Workflow with Kanban',
                'desc' => 'Make work visible, limit WIP, and use flow metrics to forecast delivery without guessing.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'dev', 'po'],
                'objs' => [
                    'Define and visualise your team\'s workflow on a Kanban board',
                    'Apply WIP limits and manage flow in real time',
                    'Measure cycle time and throughput to forecast delivery predictably',
                ],
            ],
            [
                'slug' => 'sm-i-4',
                'title' => 'Apply Evidence-Based Management',
                'desc' => 'Use EBM\'s Key Value Areas to steer with evidence instead of opinion.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pm', 'po'],
                'objs' => [
                    'Use the four EBM Key Value Areas in practice',
                    'Measure current and unrealised value',
                    'Let metrics steer strategic decisions instead of opinion',
                ],
            ],
            [
                'slug' => 'sm-i-5',
                'title' => 'Navigate Conflict Constructively',
                'desc' => 'Spot conflict early, surface different perspectives, and reach resolution without papering over.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm', 'pm'],
                'objs' => [
                    'Recognise conflict patterns before they escalate',
                    'Surface different perspectives without taking sides',
                    'Facilitate resolution without forcing false consensus',
                ],
            ],
            [
                'slug' => 'sm-a-1',
                'title' => 'Enable Self-Management and Collaborative Development',
                'desc' => 'Build the conditions for teams to self-organise — pairing, swarming, mobbing and collective ownership.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'dev', 'po'],
                'objs' => [
                    'Create the preconditions teams need to self-organise effectively',
                    'Introduce collective ownership patterns: pairing swarming and mobbing',
                    'Help teams build autonomy through small concrete agreements',
                ],
            ],
            [
                'slug' => 'sm-a-2',
                'title' => 'Set Goals with OKRs',
                'desc' => 'Write OKRs that focus on outcomes, drive experimentation, and connect teams to strategy.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pm', 'po'],
                'objs' => [
                    'Write outcome-oriented Objectives and Key Results',
                    'Connect team OKRs to organisational strategy',
                    'Use OKRs to drive experimentation and learning',
                ],
            ],
            [
                'slug' => 'sm-a-3',
                'title' => 'Engage Stakeholders Strategically',
                'desc' => 'Map stakeholders, understand their motivations, and keep alignment through change and uncertainty.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm', 'pm'],
                'objs' => [
                    'Map stakeholders and understand their motivations',
                    'Develop tailored engagement strategies',
                    'Maintain alignment through change and uncertainty',
                ],
            ],
            [
                'slug' => 'sm-a-4',
                'title' => 'Manage Technical Debt & Define Done',
                'desc' => 'Make technical debt visible, define a meaningful \'Done\', and bake quality into the workflow.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'dev'],
                'objs' => [
                    'Make technical debt visible and quantified',
                    'Establish a meaningful shared Definition of Done',
                    'Build quality controls into the development workflow',
                ],
            ],
            [
                'slug' => 'sm-a-5',
                'title' => 'Facilitate Agile Events at Scale',
                'desc' => 'Run multi-team Scrum events that surface dependencies and manage group dynamics in big rooms.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm'],
                'objs' => [
                    'Run Scrum events across multiple teams simultaneously',
                    'Surface cross-team dependencies and impediments visually',
                    'Manage group dynamics in large-room and hybrid settings',
                ],
            ],
            [
                'slug' => 'sm-a-6',
                'title' => 'Navigate Agile at Scale',
                'desc' => 'Compare scaling frameworks honestly and coordinate teams without reintroducing bureaucracy.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm', 'pm'],
                'objs' => [
                    'Compare scaling frameworks honestly (SAFe LeSS Nexus)',
                    'Spot where complexity can be reduced rather than managed',
                    'Coordinate teams without reintroducing bureaucracy',
                ],
            ],
            [
                'slug' => 'sm-e-1',
                'title' => 'Coach from Authenticity',
                'desc' => 'Recognise your own ego patterns, shift between coaching, mentoring and facilitating, and lead with less self.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm'],
                'objs' => [
                    'Recognise your own ego patterns and their impact on others',
                    'Shift between coaching mentoring and facilitating fluidly',
                    'Increase influence by reducing self-referential behaviour',
                ],
            ],
            [
                'slug' => 'sm-e-2',
                'title' => 'Apply Transactional Analysis',
                'desc' => 'Use TA to decode team dynamics and consistently coach from an adult-to-adult stance.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm'],
                'objs' => [
                    'Use TA models to decode team and interpersonal dynamics',
                    'Identify and interrupt dysfunctional communication patterns',
                    'Coach from an adult-to-adult stance consistently',
                ],
            ],
            [
                'slug' => 'sm-e-3',
                'title' => 'Manage an Agile Product Portfolio',
                'desc' => 'Govern a product portfolio against strategy and outcomes — without smothering it in process.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm', 'pjm', 'pm'],
                'objs' => [
                    'Govern a product portfolio without heavy process',
                    'Align portfolio investment to strategy and outcomes',
                    'Apply emergent design thinking across a programme',
                ],
            ],
            [
                'slug' => 'sm-e-4',
                'title' => 'Demonstrate Validated Scrum Mastery',
                'desc' => 'Integrate coaching, facilitation and Scrum fluency under pressure, in front of peers and experts.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['agile'],
                'roles' => ['sm'],
                'objs' => [
                    'Handle complex coaching scenarios under time pressure',
                    'Integrate facilitation coaching and Scrum fluency in real situations',
                    'Act on structured peer and expert feedback to reach the next level',
                ],
            ],
            [
                'slug' => 'ppm-f-1',
                'title' => 'Craft a Compelling Product Vision',
                'desc' => 'Define a vision that is structured, memorable and connected to real user and business outcomes.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po'],
                'objs' => [
                    'Define a clear structured product vision',
                    'Make the vision memorable and motivating for teams and stakeholders',
                    'Connect the vision to user and business outcomes',
                ],
            ],
            [
                'slug' => 'ppm-f-2',
                'title' => 'Tell Your Product Story',
                'desc' => 'Use story design and visualisation to communicate product direction so any audience gets it.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ba'],
                'objs' => [
                    'Use story design and narrative to communicate product direction',
                    'Apply visualisation techniques to bring your product to life',
                    'Adapt your story for different audiences and contexts',
                ],
            ],
            [
                'slug' => 'ppm-f-3',
                'title' => 'Build Customer and User Empathy',
                'desc' => 'Develop deep customer understanding through structured discovery and separate assumption from insight.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ux', 'ba'],
                'objs' => [
                    'Develop deep understanding of customer needs through structured discovery',
                    'Apply empathy mapping and user journey techniques',
                    'Separate assumptions from validated insight',
                ],
            ],
            [
                'slug' => 'ppm-f-4',
                'title' => 'Prioritise and Manage Requirements',
                'desc' => 'Capture, prioritise and validate requirements with a mix of agile and traditional techniques.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ba'],
                'objs' => [
                    'Gather and capture requirements with a holistic perspective',
                    'Apply agile and traditional prioritisation techniques',
                    'Verify and validate requirements with customers and stakeholders',
                ],
            ],
            [
                'slug' => 'ppm-i-1',
                'title' => 'Define Product Strategy',
                'desc' => 'Craft a clear product strategy tied to business goals and rally teams around outcome-oriented goals.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po'],
                'objs' => [
                    'Craft a clear product strategy tied to business objectives',
                    'Create inspiring outcome-oriented product goals',
                    'Align teams and stakeholders around strategic direction',
                ],
            ],
            [
                'slug' => 'ppm-i-2',
                'title' => 'Create Outcome-Driven Roadmaps',
                'desc' => 'Build roadmaps that communicate outcomes rather than features, and balance short and long-term bets.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'pjm'],
                'objs' => [
                    'Build roadmaps that communicate outcomes rather than features',
                    'Visualise strategy in a way stakeholders can engage with',
                    'Balance short and long-term planning within real constraints',
                ],
            ],
            [
                'slug' => 'ppm-i-3',
                'title' => 'Engage Influence and Navigate Stakeholder Politics',
                'desc' => 'Build relationships with difficult stakeholders, influence without authority, and reach alignment under pressure.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'pjm'],
                'objs' => [
                    'Build lasting relationships with even challenging stakeholders',
                    'Influence without authority and navigate political resistance',
                    'Manage conflict and reach alignment in complex situations',
                ],
            ],
            [
                'slug' => 'ppm-i-4',
                'title' => 'Run Product Discovery and Design Thinking',
                'desc' => 'Balance discovery and delivery, get to root causes, and apply ideation tools that generate real options.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ux'],
                'objs' => [
                    'Balance product discovery and delivery effectively',
                    'Apply root cause analysis and problem identification techniques',
                    'Use ideation and design thinking tools to generate innovative solutions',
                ],
            ],
            [
                'slug' => 'ppm-i-5',
                'title' => 'Measure Product Value and Apply Analytics',
                'desc' => 'Identify, measure and dashboard product value, and become genuinely data-driven.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ba'],
                'objs' => [
                    'Identify estimate and measure product value consistently',
                    'Build a value dashboard for different target audiences',
                    'Use product analytics to become a more data-driven organisation',
                ],
            ],
            [
                'slug' => 'ppm-i-6',
                'title' => 'Research Markets and Analyse Competitors',
                'desc' => 'Conduct market research, analyse competitors and apply business-model thinking to differentiate.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'ba'],
                'objs' => [
                    'Conduct market research and apply trend analysis',
                    'Analyse competitors using practical accessible tools',
                    'Apply value innovation and business model thinking to differentiate',
                ],
            ],
            [
                'slug' => 'ppm-a-1',
                'title' => 'Design and Run Product Experiments',
                'desc' => 'Run hypothesis-driven experiments with the right metrics, and make data-informed product decisions.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'dev'],
                'objs' => [
                    'Apply hypothesis-driven development in practice',
                    'Design experiments with the right KPIs and metrics',
                    'Analyse results and make data-informed product decisions',
                ],
            ],
            [
                'slug' => 'ppm-a-2',
                'title' => 'Design Inclusive and Engaging User Experiences',
                'desc' => 'Design for delight, accessibility and the established patterns that make interfaces feel right.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['ux', 'pm', 'po'],
                'objs' => [
                    'Create emotional connections and delight through product design',
                    'Apply accessibility and inclusive design principles',
                    'Design intuitive interfaces using established patterns and standards',
                ],
            ],
            [
                'slug' => 'ppm-a-3',
                'title' => 'Navigate Product Finances Budgeting and Pricing',
                'desc' => 'Work fluently with finance teams, apply agile budgeting and make defensible pricing decisions.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'pjm'],
                'objs' => [
                    'Collaborate effectively with finance and control teams',
                    'Apply agile budgeting and business case management',
                    'Make informed pricing decisions for your product',
                ],
            ],
            [
                'slug' => 'ppm-a-4',
                'title' => 'Plan and Execute a Product Launch',
                'desc' => 'Build a go-to-market strategy, plan an iterative launch, and align sales and marketing behind it.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po'],
                'objs' => [
                    'Develop a go-to-market strategy and product positioning',
                    'Plan an iterative launch process that captures real feedback',
                    'Align sales enablement and marketing for a cohesive approach',
                ],
            ],
            [
                'slug' => 'ppm-a-5',
                'title' => 'Manage a Product Portfolio at Scale',
                'desc' => 'Improve flow, manage dependencies and align portfolio investment with strategic objectives.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'pjm'],
                'objs' => [
                    'Improve value delivery and flow across a product portfolio',
                    'Manage risks dependencies and bottlenecks at portfolio level',
                    'Prioritise initiatives to align with strategic business objectives',
                ],
            ],
            [
                'slug' => 'ppm-e-1',
                'title' => 'Embed Quality Security and Compliance',
                'desc' => 'Define Done so quality, security and compliance are built in — not bolted on at the end.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'dev'],
                'objs' => [
                    'Define done in a way that ensures high-quality outcomes',
                    'Embed security and privacy considerations into product development',
                    'Align your product with relevant standards and regulatory requirements',
                ],
            ],
            [
                'slug' => 'ppm-e-2',
                'title' => 'Build Products with Data and AI',
                'desc' => 'Identify AI and data use cases and shepherd them from proof of concept to production-ready solution.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po', 'dev'],
                'objs' => [
                    'Identify and evaluate data science and AI use cases',
                    'Understand the value chain from data to business outcome',
                    'Facilitate the journey from proof of concept to production-ready solution',
                ],
            ],
            [
                'slug' => 'ppm-e-3',
                'title' => 'Lead Product-Led Growth',
                'desc' => 'Combine product-led, marketing-led and sales-led growth strategies into a sustainable engine.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['product'],
                'roles' => ['pm', 'po'],
                'objs' => [
                    'Design and execute product-led marketing-led and sales-led growth strategies',
                    'Use experimentation and analytics to drive sustainable growth',
                    'Position the product as the primary growth engine of the organisation',
                ],
            ],
            [
                'slug' => 'cl-f-1',
                'title' => 'Cloud Fundamentals',
                'desc' => 'The mental model of the cloud — compute, storage, networking and the shared-responsibility line.',
                'level' => 'foundational',
                'self_paced' => true,
                'cats' => ['cloud'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Explain core cloud building blocks',
                    'Reason about regions and availability',
                    'Understand shared responsibility',
                ],
            ],
            [
                'slug' => 'cl-f-2',
                'title' => 'Cloud Cost Awareness',
                'desc' => 'Where cloud spend comes from and how product decisions quietly drive the monthly bill.',
                'level' => 'foundational',
                'self_paced' => true,
                'cats' => ['cloud'],
                'roles' => ['pm', 'pjm', 'ba'],
                'objs' => [
                    'Read a cloud bill',
                    'Link architecture choices to cost',
                    'Spot common cost traps',
                ],
            ],
            [
                'slug' => 'cl-i-1',
                'title' => 'Cloud Architecture Patterns',
                'desc' => 'Proven patterns for resilient, scalable systems — and the trade-offs each one makes.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['cloud'],
                'roles' => ['dev'],
                'objs' => [
                    'Apply core architecture patterns',
                    'Design for resilience and scale',
                    'Reason about trade-offs',
                ],
            ],
            [
                'slug' => 'cl-i-2',
                'title' => 'Cloud Security Essentials',
                'desc' => 'Identity, secrets, network boundaries and the everyday controls that keep workloads safe.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['cloud'],
                'roles' => ['dev', 'pjm'],
                'objs' => [
                    'Apply least-privilege identity',
                    'Manage secrets and boundaries',
                    'Bake in security by default',
                ],
            ],
            [
                'slug' => 'cl-a-1',
                'title' => 'Infrastructure as Code',
                'desc' => 'Define, version and ship infrastructure the same way you ship application code.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['cloud'],
                'roles' => ['dev'],
                'objs' => [
                    'Model infrastructure declaratively',
                    'Build safe change pipelines',
                    'Manage state and drift',
                ],
            ],
            [
                'slug' => 'cl-a-2',
                'title' => 'Kubernetes in Production',
                'desc' => 'Run containerised workloads reliably — scaling, networking, observability and the hard parts.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['cloud'],
                'roles' => ['dev'],
                'objs' => [
                    'Operate workloads on Kubernetes',
                    'Scale and self-heal safely',
                    'Observe and debug clusters',
                ],
            ],
            [
                'slug' => 'cl-e-1',
                'title' => 'Multi-Cloud Strategy',
                'desc' => 'When multi-cloud is worth the complexity — and how to avoid paying for it without the benefit.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['cloud'],
                'roles' => ['pm', 'pjm'],
                'objs' => [
                    'Weigh multi-cloud trade-offs',
                    'Plan portability and exit',
                    'Govern spend across providers',
                ],
            ],
            [
                'slug' => 'ld-f-1',
                'title' => 'Leading Digital Teams',
                'desc' => 'The shift from doing the work to enabling it — how high-performing digital teams are led.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Set direction and autonomy well',
                    'Build psychological safety',
                    'Enable rather than control',
                ],
            ],
            [
                'slug' => 'ld-f-2',
                'title' => 'Coaching Conversations',
                'desc' => 'A practical coaching toolkit for everyday conversations that grow people and unblock work.',
                'level' => 'foundational',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Listen and ask powerful questions',
                    'Give feedback that lands',
                    'Coach in the flow of work',
                ],
            ],
            [
                'slug' => 'ld-i-1',
                'title' => 'Stakeholder Management',
                'desc' => 'Map, align and influence the people whose support your initiative quietly depends on.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pm', 'pjm', 'po'],
                'objs' => [
                    'Map stakeholders and interests',
                    'Tailor influence strategies',
                    'Keep alignment through change',
                ],
            ],
            [
                'slug' => 'ld-i-2',
                'title' => 'Decision-Making Under Uncertainty',
                'desc' => 'Frameworks for making good, reversible-aware decisions when you will never have full information.',
                'level' => 'competent',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pm', 'po', 'pjm', 'ba', 'ux', 'dev', 'sm'],
                'objs' => [
                    'Frame decisions and options',
                    'Reason about risk and reversibility',
                    'Decide and communicate clearly',
                ],
            ],
            [
                'slug' => 'ld-a-1',
                'title' => 'Org Design for Digital',
                'desc' => 'Shape teams, boundaries and ownership so the organisation can actually move at digital speed.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pjm', 'pm'],
                'objs' => [
                    'Design team boundaries for flow',
                    'Align ownership and incentives',
                    'Reduce hand-offs and friction',
                ],
            ],
            [
                'slug' => 'ld-a-2',
                'title' => 'Change Leadership',
                'desc' => 'Lead people through change that sticks — beyond the slide deck and the launch email.',
                'level' => 'proficient',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pjm', 'pm', 'po'],
                'objs' => [
                    'Build a credible change narrative',
                    'Mobilise sponsors and champions',
                    'Sustain new behaviours',
                ],
            ],
            [
                'slug' => 'ld-e-1',
                'title' => 'Digital Transformation Strategy',
                'desc' => 'Connect technology, operating model and culture into a transformation that delivers real value.',
                'level' => 'expert',
                'self_paced' => false,
                'cats' => ['leadership'],
                'roles' => ['pm', 'pjm'],
                'objs' => [
                    'Link strategy to operating model',
                    'Sequence transformation bets',
                    'Measure value, not activity',
                ],
            ],
        ];

        $position = 20;
        foreach ($modules as $m) {
            $type = $m['self_paced'] ? 'self-paced' : 'trainer-led';
            $this->addSql(
                "INSERT IGNORE INTO module (slug, title, description, level_id, type_id, duration_hours, position, is_active)
                 SELECT :slug, :title, :desc, l.id, t.id, 4, :position, 1
                 FROM level l, module_type t
                 WHERE l.slug = :level AND t.slug = :type",
                [
                    'slug' => $m['slug'],
                    'title' => $m['title'],
                    'desc' => $m['desc'],
                    'level' => $m['level'],
                    'type' => $type,
                    'position' => $position,
                ]
            );
            $position++;

            foreach ($m['cats'] as $catSlug) {
                $this->addSql(
                    "INSERT IGNORE INTO module_category (module_id, category_id)
                     SELECT m.id, c.id FROM module m, category c
                     WHERE m.slug = :slug AND c.slug = :cat",
                    ['slug' => $m['slug'], 'cat' => $catSlug]
                );
            }

            foreach ($m['roles'] as $roleSlug) {
                $this->addSql(
                    "INSERT IGNORE INTO module_role (module_id, role_id)
                     SELECT m.id, r.id FROM module m, role r
                     WHERE m.slug = :slug AND r.slug = :role",
                    ['slug' => $m['slug'], 'role' => $roleSlug]
                );
            }

            foreach ($m['objs'] as $objPos => $text) {
                $this->addSql(
                    "INSERT INTO module_objective (module_id, text, position)
                     SELECT m.id, :text, :pos FROM module m
                     WHERE m.slug = :slug
                     AND NOT EXISTS (
                         SELECT 1 FROM module_objective mo2
                         WHERE mo2.module_id = m.id AND mo2.position = :pos
                     )",
                    ['slug' => $m['slug'], 'text' => $text, 'pos' => $objPos]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $slugPrefixes = ['pr-', 'ppm-', 'ag-', 'sm-', 'cl-', 'ld-'];
        foreach ($slugPrefixes as $prefix) {
            $this->addSql("DELETE FROM module WHERE slug LIKE :p", ['p' => $prefix . '%']);
        }
    }
}