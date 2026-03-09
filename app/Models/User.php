<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Enums\StatutEvenement;
use App\Enums\StatutPaiement;
use App\Enums\StatutReservation;
use App\Models\Evenement;
use App\Models\Organisateur;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'telephone',
        'about',
        'role',
        'statut',
        'dateCreation',
        'interests',
        'google_id',
        'google_token',
        'google_refresh_token',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class, // Cast role to Role enum
            'interests' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * All reservations made by this user.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
    /**
     * All payments made by this user.
     */
    public function paiements(): HasManyThrough
    {
        return $this->hasManyThrough(Paiement::class, Reservation::class);
    }

    /**
     * The organizer profile linked to this user (if any).
     */
    public function organisateur(): HasOne
    {
        return $this->hasOne(Organisateur::class);
    }

    // -------------------------------------------------------------------------
    // Role Checkers
    // -------------------------------------------------------------------------

    /**
     * Returns true if this user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Returns true if this user is a participant.
     */
    public function isParticipant(): bool
    {
        return $this->role === Role::Participant;
    }

    /**
     * Returns true if this user is an organizer.
     */
    public function isOrganisateur(): bool
    {
        return $this->role === Role::Organisateur;
    }

    // -------------------------------------------------------------------------
    // Admin Methods
    // -------------------------------------------------------------------------

    /**
     * Validates an event.
     *
     * @throws AuthorizationException
     */
    public function validerEvenement(Evenement $evenement): bool
    {
        if (!$this->isAdmin()) {
            throw new AuthorizationException('Only admins can validate events.');
        }

        return $evenement->update(['statut' => StatutEvenement::Valide]);
    }

    /**
     * Blocks a user account.
     * Cannot block themselves or another admin.
     *
     * @throws AuthorizationException
     */
    public function bloquerCompte(User $user): bool
    {
        if (!$this->isAdmin()) {
            throw new AuthorizationException('Only admins can block accounts.');
        }

        if ($user->id === $this->id || $user->isAdmin()) {
            throw new AuthorizationException('You cannot block yourself or another admin.');
        }

        return $user->update(['statut' => 'blocked']);
    }

    /**
     * Soft deletes a user account.
     * Cannot delete themselves or another admin.
     *
     * @throws AuthorizationException
     */
    public function supprimerCompte(User $user): bool
    {
        if (!$this->isAdmin()) {
            throw new AuthorizationException('Only admins can delete accounts.');
        }

        if ($user->id === $this->id || $user->isAdmin()) {
            throw new AuthorizationException('You cannot delete yourself or another admin.');
        }

        return $user->delete();
    }

    /**
     * Transfers a payment to the organizer.
     *
     * @throws AuthorizationException
     */
    public function transfererPaiement(Paiement $paiement): bool
    {
        if (!$this->isAdmin()) {
            throw new AuthorizationException('Only admins can transfer payments.');
        }

        return tap($paiement)->update([
            'statut' => StatutPaiement::Transferred,
            'transferred_at' => now(),
        ])->exists;
    }

    // -------------------------------------------------------------------------
    // Participant Methods
    // -------------------------------------------------------------------------

    /**
     * Returns all validated and published events.
     *
     * @throws AuthorizationException
     */
    public function consulterEvenements(): Collection
    {
        if (!$this->isParticipant()) {
            throw new AuthorizationException('Only participants can consult events.');
        }

        return Evenement::where('statut', StatutEvenement::Valide)
            ->where('date_debut', '>', now())
            ->get();
    }

    /**
     * Reserves a spot for an event.
     *
     * @throws AuthorizationException|\Exception
     */
    public function reserverEvenement(Evenement $evenement): Reservation
    {
        if (!$this->isParticipant()) {
            throw new AuthorizationException('Only participants can reserve events.');
        }

        if ($this->hasReserved($evenement)) {
            throw new \Exception('You have already reserved this event.');
        }

        if (!$evenement->hasAvailableSpots()) {
            throw new \Exception('This event is fully booked.');
        }

        if ($evenement->date_debut->isPast()) {
            throw new \Exception('This event has already started or is in the past.');
        }

        return $this->reservations()->create([
            'evenement_id' => $evenement->id,
            'statut' => StatutReservation::Pending,
        ]);
    }

    /**
     * Creates a payment for a specific reservation.
     *
     * @throws AuthorizationException|\Exception
     */
    public function payerReservation(Reservation $reservation): Paiement
    {
        if (!$this->isParticipant()) {
            throw new AuthorizationException('Only participants can pay for reservations.');
        }

        if ($reservation->user_id !== $this->id) {
            throw new AuthorizationException('This reservation does not belong to you.');
        }

        if ($reservation->statut !== StatutReservation::Confirmed) {
            throw new \Exception('The reservation must be confirmed before paying.');
        }

        return Paiement::create([
            'reservation_id' => $reservation->id,
            'montant' => $reservation->evenement->prix_spectateur * $reservation->nombre_tickets,
            'statut' => StatutPaiement::Successful,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true if this user has an active reservation for the given event.
     */
    public function hasReserved(Evenement $event): bool
    {
        return $this->reservations()
            ->where('evenement_id', $event->id)
            ->whereIn('statut', [StatutReservation::Pending, StatutReservation::Confirmed])
            ->exists();
    }

    /**
     * Returns true if this user has an approved organizer profile.
     */
    public function isApprovedOrganisateur(): bool
    {
        return $this->organisateur !== null && $this->organisateur->isApproved();
    }
}


