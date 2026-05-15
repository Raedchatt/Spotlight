<?php

namespace App\Services;

use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Str;

class CategoryTranslationService
{
    /**
     * Translate a string into multiple languages.
     * 
     * @param string $text
     * @param array $targetLocales
     * @return array
     */
    public function translate(string $text, array $targetLocales = ['en', 'fr', 'ar']): array
    {
        $translations = [];
        $tr = new GoogleTranslate();

        foreach ($targetLocales as $locale) {
            try {
                // Special case for Arabic to ensure correct script
                $tr->setTarget($locale);
                $translations[$locale] = $tr->translate($text);
            } catch (\Exception $e) {
                \Log::error("Translation failed for locale {$locale}: " . $e->getMessage());
                $translations[$locale] = $text; // Fallback to original text
            }
        }

        return $translations;
    }

    /**
     * Create a new category from a raw string with auto-translations.
     *
     * @param string $label
     * @return \App\Models\Category
     */
    public function createTranslatedCategory(string $label)
    {
        $translations = $this->translate($label);
        
        return \App\Models\Category::create([
            'slug' => Str::slug($translations['en'] ?? $label),
            'label' => $translations,
            'is_default' => false,
        ]);
    }
}
