<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a user's request to become an organizer.
 */
class StoreOrganisateurRequest extends FormRequest
{
    /**
     * Any authenticated user can submit a request to become an organizer.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_organisation' => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'telephone'        => ['nullable', 'string', 'max:30'],
            'adresse'          => ['nullable', 'string', 'max:500'],
            'site_web'         => ['nullable', 'url', 'max:255'],
            'logo'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_organisation.required' => 'Organization name is required.',
            'site_web.url'              => 'Please provide a valid URL for the website.',
            'logo.image'                => 'Logo must be an image file (jpeg, png, jpg, webp).',
            'logo.max'                  => 'Logo must not exceed 2MB.',
        ];
    }
}
