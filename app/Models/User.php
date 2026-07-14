<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Support\OcmsAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'nik', 'password', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, \Spatie\Permission\Traits\HasRoles {
        HasTeams::teams insteadof \Spatie\Permission\Traits\HasRoles;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nik',
        'password',
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
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function canManageUsers(): bool
    {
        return OcmsAccess::canManageUsers($this);
    }

    public function hasFullOcmsAccess(): bool
    {
        return OcmsAccess::hasFullAccess($this);
    }

    public function canRegisterComponents(): bool
    {
        return OcmsAccess::canRegisterComponents($this);
    }

    public function canManageTemplates(): bool
    {
        return OcmsAccess::canManageTemplates($this);
    }

    public function canManageComponents(): bool
    {
        return OcmsAccess::canManageComponents($this);
    }

    public function canOperateOverhaul(): bool
    {
        return OcmsAccess::canOperateOverhaul($this);
    }

    public function canApproveStages(): bool
    {
        return OcmsAccess::canApproveStages($this);
    }

    public function canManageWarehouse(): bool
    {
        return OcmsAccess::canManageWarehouse($this);
    }

    public function canViewExecutiveDashboard(): bool
    {
        return OcmsAccess::canViewExecutiveDashboard($this);
    }

    public function isLogisticsReviewOnly(): bool
    {
        return OcmsAccess::isLogisticsReviewOnly($this);
    }
}
