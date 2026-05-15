<?php

namespace App\Observers;

use App\Models\Evenement;
use App\Enums\StatutEvenement;
use App\Services\CategoryTranslationService;
use App\Models\Category;

class EvenementObserver
{
    protected $translationService;

    public function __construct(CategoryTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Handle the Evenement "updated" event.
     */
    public function updated(Evenement $evenement): void
    {
        // Trigger translation when event is approved (status becomes ouvert or valide)
        $approvedStatuses = [StatutEvenement::Valide, StatutEvenement::Ouvert];
        
        if ($evenement->wasChanged('statut') && in_array($evenement->statut, $approvedStatuses)) {
            $this->handleCategoryTranslation($evenement);
        }
    }

    /**
     * Handle the category translation for "Other" categories.
     */
    protected function handleCategoryTranslation(Evenement $evenement)
    {
        if ($evenement->categorie === 'autre' && !empty($evenement->categorie_autre)) {
            // Check if this category already exists to avoid duplicates
            $existing = Category::where('slug', \Illuminate\Support\Str::slug($evenement->categorie_autre))->first();

            if ($existing) {
                $evenement->updateQuietly(['category_id' => $existing->id]);
            } else {
                // Create new translated category
                $category = $this->translationService->createTranslatedCategory($evenement->categorie_autre);
                $evenement->updateQuietly(['category_id' => $category->id]);
            }
        } else {
            // Ensure category_id is set for default categories if it was somehow missed
            $category = Category::where('slug', $evenement->categorie)->first();
            if ($category) {
                $evenement->updateQuietly(['category_id' => $category->id]);
            }
        }
    }
}
