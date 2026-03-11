<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validates organizer profile update requests.
 * Only the owner of the organizer profile can submit this request.
 */
class UpdateOrganisateurRequest extends FormRequest
{
    /**
     * Only the organizer themselves may update their profile.
     * Gate check is also done in the controller, but early rejection here saves a DB round-trip.
     */
    public function authorize(): bool
    {
        $organisateur = $this->route('organisateur');

        return $organisateur && $organisateur->user_id === Auth::id();
    }

    public function rules(): array
    {
        return [
            'nom_organisation' => ['sometimes', 'required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'telephone'        => ['nullable', 'string', 'max:30'],
            'adresse'          => ['nullable', 'string', 'max:500'],
            'site_web'         => ['nullable', 'url', 'max:255'],
            'logo'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'rib'              => ['nullable', 'string', 'max:30'],
            'rib_popup_seen'   => ['sometimes', 'boolean'],
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
