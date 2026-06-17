<?php

namespace App\Service;

/**
 * Backwards-compatible facade over {@see JourneyCollection}. Existing templates use this as
 * the Twig global `journey`. Methods operate on the *active* journey or the global collection
 * as appropriate.
 *
 * Two methods have semantically shifted with the move to multi-journey/DB-backed support:
 *  - isSelected($slug) → true if the slug is in ANY journey (so the card "Selected" badge
 *    remains useful when a module belongs to a non-active journey).
 *  - count()           → number of journeys (drives the header "Journeys (N)" pill).
 */
class JourneyBuilder
{
    public function __construct(private readonly JourneyCollection $journeys)
    {
    }

    /* --- mode --- */

    public function isOn(): bool
    {
        return $this->journeys->isOn();
    }

    public function toggleMode(): bool
    {
        return $this->journeys->toggleMode();
    }

    /* --- active journey accessors --- */

    /** @return string[] */
    public function getSelectedSlugs(): array
    {
        return $this->journeys->getActive()?->getModuleSlugs() ?? [];
    }

    public function isSelected(string $slug): bool
    {
        return count($this->journeys->journeysContainingSlug($slug)) > 0;
    }

    /** Number of *journeys* (NB: redefined for multi-journey support). */
    public function count(): int
    {
        return $this->journeys->count();
    }

    public function getRoleName(): string
    {
        return $this->journeys->getActive()?->getAudience() ?? '';
    }

    /* --- active-journey mutations (legacy compat) --- */

    public function select(string $slug): void
    {
        $j = $this->journeys->getActive();
        if (!$j) {
            return; // cannot create on the fly anymore — needs a client
        }
        $this->journeys->addSlugTo($j->getId(), $slug);
    }

    public function deselect(string $slug): void
    {
        $j = $this->journeys->getActive();
        if (!$j) {
            return;
        }
        $this->journeys->removeSlugFrom($j->getId(), $slug);
    }

    public function clear(): void
    {
        $j = $this->journeys->getActive();
        if (!$j) {
            return;
        }
        $j->setModuleSlugs([]);
        $j->setAudience('');
        // Flush via reorderWithin([]) — easier than exposing flush from the collection.
        $this->journeys->reorderWithin($j->getId(), []);
    }

    /** @param string[] $slugs */
    public function reorder(array $slugs): void
    {
        $j = $this->journeys->getActive();
        if (!$j) {
            return;
        }
        $this->journeys->reorderWithin($j->getId(), $slugs);
    }
}
