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

        $this->createModules($manager, $levels, $categories, $roles, $types);
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
            ['foundational', 'Foundational', 1, '#B75EB8', '#FBF5FB', 'Universal literacy and role-specific entry points.'],
            ['intermediate', 'Intermediate', 2, '#9A3C9B', '#F8EFF8', 'Where roles begin to diverge meaningfully.'],
            ['advanced',     'Advanced',     3, '#7C1B7B', '#F4EAF4', 'The applied craft layer — mostly cross-role.'],
            ['expert',       'Expert',       4, '#561257', '#F0E5F0', 'Deep specialism and strategic edge cases.'],
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

    private function createModules(ObjectManager $manager, array $levels, array $cats, array $roles, array $types): void
    {
        $ALL    = ['pm','po','pjm','ba','ux','dev'];
        $NONTECH = ['pm','po','pjm','ba','ux'];
        $SELF_PACED = ['ai-f-1','ai-f-2','ai-f-3','cl-f-1','cl-f-2','ag-f-1','pr-f-1','pr-f-2'];

        $modules = [
            /* ===== AI ===== */
            ['ai-f-1','ai','foundational','What AI Really Is',$ALL,
                'A jargon-free grounding in how modern AI works, what it can and cannot do, and where it genuinely creates value.',
                ['Distinguish rules, machine learning and generative AI','Separate realistic use cases from hype','Build a shared AI vocabulary across the team']],
            ['ai-f-2','ai','foundational','Data Literacy Basics',$ALL,
                'How data is collected, shaped and trusted — the raw material every AI system quietly depends on.',
                ['Read and interrogate a dataset','Reason about bias, quality and provenance','Judge when data is fit for purpose']],
            ['ai-f-3','ai','foundational','AI Use Cases & Terminology',$NONTECH,
                'A guided tour of high-value AI use cases mapped directly onto product and delivery work.',
                ['Map AI capabilities to real product problems','Speak confidently with technical teams','Spot opportunities and red flags early']],
            ['ai-f-4','ai','foundational','Python for AI',['dev'],
                'Set up and write the practical Python you need to work with data, notebooks and models.',
                ['Work fluently in notebooks and scripts','Manipulate data with pandas and numpy','Call and inspect a model end to end']],
            ['ai-f-5','ai','foundational','ML Environment Setup',['dev'],
                'A clean, reproducible local and cloud environment so experiments run the same way every time.',
                ['Configure virtual environments and dependencies','Use GPUs and managed runtimes','Make experiments reproducible']],
            ['ai-f-6','ai','foundational','Designing for AI: A Primer',['ux'],
                'The interaction patterns, trust signals and failure states that make AI features feel usable.',
                ['Apply core AI interaction patterns','Design for uncertainty and error','Set honest user expectations']],
            ['ai-i-1','ai','intermediate','Writing AI Product Requirements',['pm','po'],
                'Translate fuzzy AI ambitions into requirements engineers and data scientists can actually build against.',
                ['Frame problems as testable requirements','Specify data and model expectations','Plan for ambiguity and iteration']],
            ['ai-i-2','ai','intermediate','Defining AI Success Metrics',['pm','po'],
                'Choose metrics that capture both model quality and real product value — and avoid vanity numbers.',
                ['Separate model metrics from product metrics','Design guardrail and counter-metrics','Instrument outcomes you can act on']],
            ['ai-i-3','ai','intermediate','AI Risk Management',['pjm'],
                'Identify, size and mitigate the delivery, ethical and operational risks unique to AI initiatives.',
                ['Build an AI-specific risk register','Plan mitigations and fallbacks','Communicate risk to stakeholders']],
            ['ai-i-4','ai','intermediate','Vendor & Tooling Evaluation',['pjm','ba'],
                'A structured way to compare models, platforms and vendors against cost, fit and lock-in.',
                ['Run a fair, weighted evaluation','Probe cost, security and lock-in','Make a defensible recommendation']],
            ['ai-i-5','ai','intermediate','Data Pipelines for ML',['dev'],
                'Move data reliably from source to model — ingestion, transformation, validation and scheduling.',
                ['Design resilient ingestion and transforms','Validate and monitor data quality','Schedule and observe pipelines']],
            ['ai-i-6','ai','intermediate','Working with AI APIs',['dev'],
                'Integrate hosted and open models cleanly — auth, streaming, retries, cost control and caching.',
                ['Integrate model APIs robustly','Handle streaming, retries and limits','Control latency and cost']],
            ['ai-i-7','ai','intermediate','Specifying AI Data Requirements',['ba'],
                "Define exactly what data a use case needs, where it lives and what \"good enough\" looks like.",
                ['Trace data lineage to a use case','Write clear data acceptance criteria','Flag gaps, consent and quality issues']],
            ['ai-i-8','ai','intermediate','Prototyping AI Interactions',['ux'],
                'Rapidly prototype AI-driven flows with realistic, sometimes-wrong outputs to test the real experience.',
                ['Prototype with believable AI behaviour','Test trust, control and recovery','Iterate from real user reactions']],
            ['ai-a-1','ai','advanced','Retrieval-Augmented Generation',['dev','pm'],
                'Ground language models in your own knowledge with retrieval, chunking, embeddings and evaluation.',
                ['Design a RAG architecture end to end','Tune retrieval and chunking quality','Evaluate grounded answer accuracy']],
            ['ai-a-2','ai','advanced','LLMOps Fundamentals',['dev'],
                'Ship and operate LLM features safely — versioning, evals, monitoring, prompts and rollbacks.',
                ['Version prompts, models and datasets','Run continuous evals in CI','Monitor, alert and roll back']],
            ['ai-a-3','ai','advanced','AI Governance & Compliance',['pjm','po','ba'],
                'Operationalise the EU AI Act and internal policy into practical controls teams can actually follow.',
                ['Classify systems by risk tier','Embed controls into delivery','Maintain auditable documentation']],
            ['ai-a-4','ai','advanced','Agentic Systems Design',['dev','pm'],
                'Design multi-step agents that plan, call tools and stay reliable, observable and safe in production.',
                ['Architect tool-using agents','Add guardrails and human checkpoints','Trace and debug agent behaviour']],
            ['ai-a-5','ai','advanced','AI UX Research',['ux','pm'],
                'Research methods tuned for probabilistic products where the same input can give different answers.',
                ['Study trust, reliance and overreliance','Test non-deterministic experiences','Turn findings into design decisions']],
            ['ai-e-1','ai','expert','Transformer Architectures',['dev'],
                'A deep look inside attention, embeddings and the architectures behind modern foundation models.',
                ['Explain attention and positional encoding','Reason about architecture trade-offs','Read and adapt model code']],
            ['ai-e-2','ai','expert','Distributed Training',['dev'],
                'Train large models across many GPUs with data, tensor and pipeline parallelism — without waste.',
                ['Choose a parallelism strategy','Diagnose throughput bottlenecks','Manage cost and checkpoints']],
            ['ai-e-3','ai','expert','Inference Optimisation',['dev'],
                'Make models fast and cheap to serve — quantisation, batching, caching and hardware-aware tuning.',
                ['Apply quantisation and distillation','Tune batching and KV caching','Hit latency and cost targets']],
            ['ai-e-4','ai','expert','AI Monetisation Models',['pm'],
                'Pricing and packaging for AI products where unit costs are real, variable and usage-driven.',
                ['Model usage-based unit economics','Design pricing and packaging','Protect margin as usage scales']],
            ['ai-e-5','ai','expert','AI Safety in Product Spec',['pm','po'],
                'Bake safety, misuse and abuse considerations into specs before a single line is written.',
                ['Run structured misuse analysis','Specify safety requirements','Define acceptable-use boundaries']],
            ['ai-e-6','ai','expert','Model Evaluation & Benchmarking',['dev','pm'],
                'Design evaluations that actually predict real-world quality — beyond leaderboard benchmarks.',
                ['Build task-specific eval sets','Combine automated and human evals','Detect regressions before release']],
            ['ai-e-7','ai','expert','Red-Teaming AI Systems',['dev','pjm'],
                'Adversarially probe models and agents for jailbreaks, leakage and failure before attackers do.',
                ['Plan and run red-team exercises','Surface jailbreaks and data leakage','Feed findings into mitigations']],
            /* ===== Product ===== */
            ['pr-f-1','product','foundational','Product Thinking Fundamentals',$ALL,
                'The mindset shift from building features to solving real problems for real people.',
                ['Frame work as problems, not features','Connect work to user and business value','Adopt an outcome-first mindset']],
            ['pr-f-2','product','foundational','Discovery vs Delivery',$ALL,
                'How great teams continuously discover what to build while reliably delivering what they have decided.',
                ['Separate discovery from delivery','Run lightweight discovery','Reduce waste and rework']],
            ['pr-i-1','product','intermediate','Outcome-Based Roadmapping',['pm','po'],
                'Build roadmaps around outcomes and bets instead of fixed feature lists and dates.',
                ['Frame roadmaps around outcomes','Communicate uncertainty honestly','Align stakeholders on bets']],
            ['pr-i-2','product','intermediate','Customer Interviewing',['pm','po','ux','ba'],
                'Run interviews that surface real needs and behaviours — not the answers you hoped to hear.',
                ['Recruit and structure interviews','Ask non-leading questions','Synthesise signal from noise']],
            ['pr-i-3','product','intermediate','Prioritisation Frameworks',['pm','po','pjm'],
                'Practical frameworks for deciding what to do next when everything feels important.',
                ['Apply value-vs-effort thinking','Use frameworks without dogma','Make and defend trade-offs']],
            ['pr-a-1','product','advanced','Product Strategy & Vision',['pm','po'],
                'Craft a vision and strategy that gives teams a clear, motivating direction to make decisions against.',
                ['Articulate a compelling vision','Translate strategy into focus','Cascade strategy to teams']],
            ['pr-a-2','product','advanced','Experimentation & A/B Testing',['pm','dev'],
                'Design and read experiments that actually tell you whether a change moved the needle.',
                ['Design sound experiments','Reason about significance','Avoid common testing traps']],
            ['pr-e-1','product','expert','Product Operating Model',['pm','pjm'],
                'Shape how product, design and engineering work together to deliver value at scale.',
                ['Design an operating model','Balance autonomy and alignment','Evolve the model over time']],
            /* ===== Agile ===== */
            ['ag-f-1','agile','foundational','Agile Mindset & Values',$ALL,
                'The principles behind agile ways of working and why they change how teams decide and deliver.',
                ['Internalise the agile values','Recognise agile vs cargo-cult agile','Apply an empirical mindset']],
            ['ag-f-2','agile','foundational','Scrum in Practice',$ALL,
                'Roles, events and artefacts of Scrum, grounded in how real teams run them day to day.',
                ['Run the core Scrum events well','Clarify roles and accountabilities','Avoid common Scrum anti-patterns']],
            ['ag-i-1','agile','intermediate','Backlog Refinement',['po','pm'],
                'Keep a backlog that is ready, valuable and just-enough — without drowning in detail.',
                ['Slice and order work by value','Keep items ready for delivery','Balance discovery and delivery']],
            ['ag-i-2','agile','intermediate','Facilitating Agile Ceremonies',['pjm'],
                'Facilitation techniques that make planning, reviews and retros genuinely useful.',
                ['Design and run focused ceremonies','Surface conflict productively','Drive clear decisions and actions']],
            ['ag-i-3','agile','intermediate','User Story Mapping',['pm','po','ux'],
                'Build a shared, visual model of the user journey to plan releases around real outcomes.',
                ['Map journeys to a release plan','Find gaps and slice thin releases','Align the team on outcomes']],
            ['ag-a-1','agile','advanced','Scaling Agile (SAFe / LeSS)',['pjm','pm'],
                'Coordinate many teams toward one product without recreating the bureaucracy agile replaced.',
                ['Compare scaling frameworks honestly','Coordinate cross-team dependencies','Keep flow at scale']],
            ['ag-a-2','agile','advanced','Agile Metrics & Flow',['pm','pjm'],
                'Measure flow and outcomes — not velocity theatre — to improve how work actually moves.',
                ['Read flow and lead-time metrics','Spot and remove bottlenecks','Improve predictability']],
            ['ag-e-1','agile','expert','Enterprise Agile Coaching',['pjm'],
                'Coach leaders and teams through deep, organisation-wide changes in how work happens.',
                ['Coach at team and org level','Navigate resistance and politics','Sustain change over time']],
            /* ===== Scrum Master (Agile) ===== */
            ['sm-f-1','agile','foundational','Foster Psychological Safety',['sm','po','pjm'],
                'The everyday practices that make it safe to speak up, disagree and ask for help — the prerequisite for any real team performance.',
                ['Build a toolkit for creating safety in teams','Recognise and address fear and anxiety at work','Establish norms that enable honest and open communication']],
            ['sm-f-2','agile','foundational','Give and Receive Feedback',['sm','po','pjm'],
                'How to give feedback that actually changes behaviour, and how to receive it without flinching.',
                ['Deliver constructive feedback that actually lands','Receive feedback without defensiveness','Establish a peer feedback rhythm in your team']],
            ['sm-f-3','agile','foundational','Teach Agile Values & Principles',['sm','pjm','pm'],
                'Beyond ceremonies — the values and principles behind agile, and how to explain them so they stick.',
                ['Explain the Agile Manifesto in your own words','Connect values to everyday team decisions','Distinguish agile mindset from agile process']],
            ['sm-f-4','agile','foundational','Teach Scrum Foundations',['sm','po','dev'],
                "Scrum's accountabilities, events and artifacts taught through play and experience, not slides.",
                ["Train others in Scrum's accountabilities events and artifacts",'Apply empiricism to inspect and adapt','Run a Scrum cycle experientially using games and simulation']],
            ['sm-f-5','agile','foundational','Ask Powerful Questions',['sm','pjm','pm'],
                'The coaching habit of holding back advice and asking the question that opens new thinking.',
                ['Listen deeply without jumping to solutions','Ask questions that open thinking rather than close it','Distinguish a coaching conversation from advice-giving']],
            ['sm-f-6','agile','foundational','Design Playful Learning Experiences',['sm','pjm'],
                'Use games, simulations and physical play to make abstract agile ideas tangible and memorable.',
                ['Use games and simulations to make abstract concepts tangible','Create safety and energy through play','Design learning activities grounded in neuroscience']],
            ['sm-i-1','agile','intermediate','Facilitate Scrum Events',['sm','po'],
                'Run Planning, Daily, Review and Retrospective so they stay purposeful, honest and time-boxed.',
                ['Facilitate Planning Daily Review and Retrospective with skill','Keep events purposeful time-boxed and engaging','Foster honest inspection and psychological safety in every event']],
            ['sm-i-2','agile','intermediate','Bring Scrum Values to Life',['sm','po','dev'],
                "Move Scrum's five values from posters on the wall into the everyday behaviour of the team.",
                ['Recognise when values are lived or violated in practice','Roleplay and debrief challenging value-conflict scenarios','Design plays that embed values into daily team behaviour']],
            ['sm-i-3','agile','intermediate','Manage Visualise and Measure Workflow with Kanban',['sm','dev','po'],
                'Make work visible, limit WIP, and use flow metrics to forecast delivery without guessing.',
                ["Define and visualise your team's workflow on a Kanban board",'Apply WIP limits and manage flow in real time','Measure cycle time and throughput to forecast delivery predictably']],
            ['sm-i-4','agile','intermediate','Apply Evidence-Based Management',['sm','pm','po'],
                "Use EBM's Key Value Areas to steer with evidence instead of opinion.",
                ['Use the four EBM Key Value Areas in practice','Measure current and unrealised value','Let metrics steer strategic decisions instead of opinion']],
            ['sm-i-5','agile','intermediate','Navigate Conflict Constructively',['sm','pjm','pm'],
                'Spot conflict early, surface different perspectives, and reach resolution without papering over.',
                ['Recognise conflict patterns before they escalate','Surface different perspectives without taking sides','Facilitate resolution without forcing false consensus']],
            ['sm-a-1','agile','advanced','Enable Self-Management and Collaborative Development',['sm','dev','po'],
                'Build the conditions for teams to self-organise — pairing, swarming, mobbing and collective ownership.',
                ['Create the preconditions teams need to self-organise effectively','Introduce collective ownership patterns: pairing swarming and mobbing','Help teams build autonomy through small concrete agreements']],
            ['sm-a-2','agile','advanced','Set Goals with OKRs',['sm','pm','po'],
                'Write OKRs that focus on outcomes, drive experimentation, and connect teams to strategy.',
                ['Write outcome-oriented Objectives and Key Results','Connect team OKRs to organisational strategy','Use OKRs to drive experimentation and learning']],
            ['sm-a-3','agile','advanced','Engage Stakeholders Strategically',['sm','pjm','pm'],
                'Map stakeholders, understand their motivations, and keep alignment through change and uncertainty.',
                ['Map stakeholders and understand their motivations','Develop tailored engagement strategies','Maintain alignment through change and uncertainty']],
            ['sm-a-4','agile','advanced','Manage Technical Debt & Define Done',['sm','dev'],
                "Make technical debt visible, define a meaningful 'Done', and bake quality into the workflow.",
                ['Make technical debt visible and quantified','Establish a meaningful shared Definition of Done','Build quality controls into the development workflow']],
            ['sm-a-5','agile','advanced','Facilitate Agile Events at Scale',['sm','pjm'],
                'Run multi-team Scrum events that surface dependencies and manage group dynamics in big rooms.',
                ['Run Scrum events across multiple teams simultaneously','Surface cross-team dependencies and impediments visually','Manage group dynamics in large-room and hybrid settings']],
            ['sm-a-6','agile','advanced','Navigate Agile at Scale',['sm','pjm','pm'],
                'Compare scaling frameworks honestly and coordinate teams without reintroducing bureaucracy.',
                ['Compare scaling frameworks honestly (SAFe LeSS Nexus)','Spot where complexity can be reduced rather than managed','Coordinate teams without reintroducing bureaucracy']],
            ['sm-e-1','agile','expert','Coach from Authenticity',['sm','pjm'],
                'Recognise your own ego patterns, shift between coaching, mentoring and facilitating, and lead with less self.',
                ['Recognise your own ego patterns and their impact on others','Shift between coaching mentoring and facilitating fluidly','Increase influence by reducing self-referential behaviour']],
            ['sm-e-2','agile','expert','Apply Transactional Analysis',['sm','pjm'],
                'Use TA to decode team dynamics and consistently coach from an adult-to-adult stance.',
                ['Use TA models to decode team and interpersonal dynamics','Identify and interrupt dysfunctional communication patterns','Coach from an adult-to-adult stance consistently']],
            ['sm-e-3','agile','expert','Manage an Agile Product Portfolio',['sm','pjm','pm'],
                'Govern a product portfolio against strategy and outcomes — without smothering it in process.',
                ['Govern a product portfolio without heavy process','Align portfolio investment to strategy and outcomes','Apply emergent design thinking across a programme']],
            ['sm-e-4','agile','expert','Demonstrate Validated Scrum Mastery',['sm'],
                'Integrate coaching, facilitation and Scrum fluency under pressure, in front of peers and experts.',
                ['Handle complex coaching scenarios under time pressure','Integrate facilitation coaching and Scrum fluency in real situations','Act on structured peer and expert feedback to reach the next level']],
            /* ===== Product & Project Management ===== */
            ['ppm-f-1','product','foundational','Craft a Compelling Product Vision',['pm','po'],
                'Define a vision that is structured, memorable and connected to real user and business outcomes.',
                ['Define a clear structured product vision','Make the vision memorable and motivating for teams and stakeholders','Connect the vision to user and business outcomes']],
            ['ppm-f-2','product','foundational','Tell Your Product Story',['pm','po','ba'],
                'Use story design and visualisation to communicate product direction so any audience gets it.',
                ['Use story design and narrative to communicate product direction','Apply visualisation techniques to bring your product to life','Adapt your story for different audiences and contexts']],
            ['ppm-f-3','product','foundational','Build Customer and User Empathy',['pm','po','ux','ba'],
                'Develop deep customer understanding through structured discovery and separate assumption from insight.',
                ['Develop deep understanding of customer needs through structured discovery','Apply empathy mapping and user journey techniques','Separate assumptions from validated insight']],
            ['ppm-f-4','product','foundational','Prioritise and Manage Requirements',['pm','po','ba'],
                'Capture, prioritise and validate requirements with a mix of agile and traditional techniques.',
                ['Gather and capture requirements with a holistic perspective','Apply agile and traditional prioritisation techniques','Verify and validate requirements with customers and stakeholders']],
            ['ppm-i-1','product','intermediate','Define Product Strategy',['pm','po'],
                'Craft a clear product strategy tied to business goals and rally teams around outcome-oriented goals.',
                ['Craft a clear product strategy tied to business objectives','Create inspiring outcome-oriented product goals','Align teams and stakeholders around strategic direction']],
            ['ppm-i-2','product','intermediate','Create Outcome-Driven Roadmaps',['pm','po','pjm'],
                'Build roadmaps that communicate outcomes rather than features, and balance short and long-term bets.',
                ['Build roadmaps that communicate outcomes rather than features','Visualise strategy in a way stakeholders can engage with','Balance short and long-term planning within real constraints']],
            ['ppm-i-3','product','intermediate','Engage Influence and Navigate Stakeholder Politics',['pm','po','pjm'],
                'Build relationships with difficult stakeholders, influence without authority, and reach alignment under pressure.',
                ['Build lasting relationships with even challenging stakeholders','Influence without authority and navigate political resistance','Manage conflict and reach alignment in complex situations']],
            ['ppm-i-4','product','intermediate','Run Product Discovery and Design Thinking',['pm','po','ux'],
                'Balance discovery and delivery, get to root causes, and apply ideation tools that generate real options.',
                ['Balance product discovery and delivery effectively','Apply root cause analysis and problem identification techniques','Use ideation and design thinking tools to generate innovative solutions']],
            ['ppm-i-5','product','intermediate','Measure Product Value and Apply Analytics',['pm','po','ba'],
                'Identify, measure and dashboard product value, and become genuinely data-driven.',
                ['Identify estimate and measure product value consistently','Build a value dashboard for different target audiences','Use product analytics to become a more data-driven organisation']],
            ['ppm-i-6','product','intermediate','Research Markets and Analyse Competitors',['pm','po','ba'],
                'Conduct market research, analyse competitors and apply business-model thinking to differentiate.',
                ['Conduct market research and apply trend analysis','Analyse competitors using practical accessible tools','Apply value innovation and business model thinking to differentiate']],
            ['ppm-a-1','product','advanced','Design and Run Product Experiments',['pm','po','dev'],
                'Run hypothesis-driven experiments with the right metrics, and make data-informed product decisions.',
                ['Apply hypothesis-driven development in practice','Design experiments with the right KPIs and metrics','Analyse results and make data-informed product decisions']],
            ['ppm-a-2','product','advanced','Design Inclusive and Engaging User Experiences',['ux','pm','po'],
                'Design for delight, accessibility and the established patterns that make interfaces feel right.',
                ['Create emotional connections and delight through product design','Apply accessibility and inclusive design principles','Design intuitive interfaces using established patterns and standards']],
            ['ppm-a-3','product','advanced','Navigate Product Finances Budgeting and Pricing',['pm','pjm'],
                'Work fluently with finance teams, apply agile budgeting and make defensible pricing decisions.',
                ['Collaborate effectively with finance and control teams','Apply agile budgeting and business case management','Make informed pricing decisions for your product']],
            ['ppm-a-4','product','advanced','Plan and Execute a Product Launch',['pm','po'],
                'Build a go-to-market strategy, plan an iterative launch, and align sales and marketing behind it.',
                ['Develop a go-to-market strategy and product positioning','Plan an iterative launch process that captures real feedback','Align sales enablement and marketing for a cohesive approach']],
            ['ppm-a-5','product','advanced','Manage a Product Portfolio at Scale',['pm','pjm'],
                'Improve flow, manage dependencies and align portfolio investment with strategic objectives.',
                ['Improve value delivery and flow across a product portfolio','Manage risks dependencies and bottlenecks at portfolio level','Prioritise initiatives to align with strategic business objectives']],
            ['ppm-e-1','product','expert','Embed Quality Security and Compliance',['pm','po','dev'],
                'Define Done so quality, security and compliance are built in — not bolted on at the end.',
                ['Define done in a way that ensures high-quality outcomes','Embed security and privacy considerations into product development','Align your product with relevant standards and regulatory requirements']],
            ['ppm-e-2','product','expert','Build Products with Data and AI',['pm','po','dev'],
                'Identify AI and data use cases and shepherd them from proof of concept to production-ready solution.',
                ['Identify and evaluate data science and AI use cases','Understand the value chain from data to business outcome','Facilitate the journey from proof of concept to production-ready solution']],
            ['ppm-e-3','product','expert','Lead Product-Led Growth',['pm','po'],
                'Combine product-led, marketing-led and sales-led growth strategies into a sustainable engine.',
                ['Design and execute product-led marketing-led and sales-led growth strategies','Use experimentation and analytics to drive sustainable growth','Position the product as the primary growth engine of the organisation']],
            /* ===== Cloud ===== */
            ['cl-f-1','cloud','foundational','Cloud Fundamentals',$ALL,
                'The mental model of the cloud — compute, storage, networking and the shared-responsibility line.',
                ['Explain core cloud building blocks','Reason about regions and availability','Understand shared responsibility']],
            ['cl-f-2','cloud','foundational','Cloud Cost Awareness',['pm','pjm','ba'],
                'Where cloud spend comes from and how product decisions quietly drive the monthly bill.',
                ['Read a cloud bill','Link architecture choices to cost','Spot common cost traps']],
            ['cl-i-1','cloud','intermediate','Cloud Architecture Patterns',['dev'],
                'Proven patterns for resilient, scalable systems — and the trade-offs each one makes.',
                ['Apply core architecture patterns','Design for resilience and scale','Reason about trade-offs']],
            ['cl-i-2','cloud','intermediate','Cloud Security Essentials',['dev','pjm'],
                'Identity, secrets, network boundaries and the everyday controls that keep workloads safe.',
                ['Apply least-privilege identity','Manage secrets and boundaries','Bake in security by default']],
            ['cl-a-1','cloud','advanced','Infrastructure as Code',['dev'],
                'Define, version and ship infrastructure the same way you ship application code.',
                ['Model infrastructure declaratively','Build safe change pipelines','Manage state and drift']],
            ['cl-a-2','cloud','advanced','Kubernetes in Production',['dev'],
                'Run containerised workloads reliably — scaling, networking, observability and the hard parts.',
                ['Operate workloads on Kubernetes','Scale and self-heal safely','Observe and debug clusters']],
            ['cl-e-1','cloud','expert','Multi-Cloud Strategy',['pm','pjm'],
                'When multi-cloud is worth the complexity — and how to avoid paying for it without the benefit.',
                ['Weigh multi-cloud trade-offs','Plan portability and exit','Govern spend across providers']],
            /* ===== Leadership ===== */
            ['ld-f-1','leadership','foundational','Leading Digital Teams',$ALL,
                'The shift from doing the work to enabling it — how high-performing digital teams are led.',
                ['Set direction and autonomy well','Build psychological safety','Enable rather than control']],
            ['ld-f-2','leadership','foundational','Coaching Conversations',$ALL,
                'A practical coaching toolkit for everyday conversations that grow people and unblock work.',
                ['Listen and ask powerful questions','Give feedback that lands','Coach in the flow of work']],
            ['ld-i-1','leadership','intermediate','Stakeholder Management',['pm','pjm','po'],
                "Map, align and influence the people whose support your initiative quietly depends on.",
                ['Map stakeholders and interests','Tailor influence strategies','Keep alignment through change']],
            ['ld-i-2','leadership','intermediate','Decision-Making Under Uncertainty',$ALL,
                'Frameworks for making good, reversible-aware decisions when you will never have full information.',
                ['Frame decisions and options','Reason about risk and reversibility','Decide and communicate clearly']],
            ['ld-a-1','leadership','advanced','Org Design for Digital',['pjm','pm'],
                'Shape teams, boundaries and ownership so the organisation can actually move at digital speed.',
                ['Design team boundaries for flow','Align ownership and incentives','Reduce hand-offs and friction']],
            ['ld-a-2','leadership','advanced','Change Leadership',['pjm','pm','po'],
                'Lead people through change that sticks — beyond the slide deck and the launch email.',
                ['Build a credible change narrative','Mobilise sponsors and champions','Sustain new behaviours']],
            ['ld-e-1','leadership','expert','Digital Transformation Strategy',['pm','pjm'],
                'Connect technology, operating model and culture into a transformation that delivers real value.',
                ['Link strategy to operating model','Sequence transformation bets','Measure value, not activity']],
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

        foreach ($modules as $pos => [$id, $catSlug, $levelSlug, $title, $roleIds, $desc, $objTexts]) {
            $module = (new Module())
                ->setTitle($title)
                ->setSlug($id)
                ->setDescription($desc)
                ->setLevel($levels[$levelSlug])
                ->setType(in_array($id, $SELF_PACED, true) ? $types['self-paced'] : $types['trainer-led'])
                ->setPosition($pos);

            $module->addCategory($cats[$catSlug]);
            foreach ($roleIds as $rid) {
                $module->addRole($roles[$rid]);
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
}
