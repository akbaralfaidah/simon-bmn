<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\LoanRequest;
use App\Models\OrganizationUnit;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessScope
{
    public const COORDINATORS = ['Koordinator', 'Koordinator BMN'];

    public const KEEPERS = ['Penanggung Jawab Ruangan', 'PJ Ruangan'];

    public function assignments(User $user, array $roles): HasMany
    {
        return $user->roleAssignments()->whereHas('role', fn (Builder $query) => $query->whereIn('name', $roles))
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function global(User $user): bool
    {
        return $user->status === 'active' && $this->assignments($user, self::COORDINATORS)->where('is_global', true)->exists();
    }

    public function administer(User $user): bool
    {
        return $user->status === 'active' && $this->assignments($user, self::COORDINATORS)->whereNull('room_id')->where('can_administer', true)->exists();
    }

    public function roomIds(User $user, bool $operational = false, ?array $roles = null): array
    {
        if ($user->status !== 'active') {
            return [];
        }
        if ($this->global($user) && ($roles === null || $roles === self::COORDINATORS)) {
            return Room::pluck('id')->all();
        }
        $assignments = $this->assignments($user, $roles ?? [...self::COORDINATORS, ...self::KEEPERS])->get();
        $units = $assignments->whereNull('room_id')->pluck('unit_id')->filter()->all();
        if (! $operational && $user->profile?->unit_id) {
            $units[] = $user->profile->unit_id;
        }

        return Room::whereIn('unit_id', $units)->orWhereIn('id', $assignments->pluck('room_id')->filter())->pluck('id')->all();
    }

    public function administrativeUnitIds(User $user): array
    {
        if (! $this->administer($user)) {
            return [];
        }
        if ($this->global($user)) {
            return OrganizationUnit::pluck('id')->all();
        }

        return $this->assignments($user, self::COORDINATORS)->whereNull('room_id')->where('can_administer', true)->pluck('unit_id')->filter()->unique()->values()->all();
    }

    public function unitIds(User $user): array
    {
        if ($this->global($user)) {
            return OrganizationUnit::pluck('id')->all();
        }

        return $this->assignments($user, [...self::COORDINATORS, ...self::KEEPERS])->whereNull('room_id')->pluck('unit_id')
            ->merge(Room::whereIn('id', $this->roomIds($user, true))->pluck('unit_id'))->filter()->unique()->values()->all();
    }

    public function assets(User $user, bool $operational = false): Builder
    {
        if ($user->status !== 'active') {
            return Asset::whereRaw('1 = 0');
        }

        if (! $operational || $this->global($user)) {
            return Asset::query();
        }

        return Asset::whereIn('room_id', $this->roomIds($user, $operational));
    }

    public function coordinate(User $user, Asset $asset): bool
    {
        return $this->global($user) || in_array($asset->room_id, $this->roomIds($user, true, self::COORDINATORS));
    }

    public function inspect(User $user, Asset $asset): bool
    {
        return in_array($asset->room_id, $this->roomIds($user, true, self::KEEPERS));
    }

    public function viewLoan(User $user, LoanRequest $loan): bool
    {
        return $loan->user_id === $user->id || ($loan->items->isNotEmpty() && $loan->items->every(
            fn ($item) => $this->coordinate($user, $item->asset) || $this->inspect($user, $item->asset)
        ));
    }

    public function decideLoan(User $user, LoanRequest $loan): bool
    {
        return $loan->user_id !== $user->id && $loan->items->isNotEmpty() && $loan->items->every(
            fn ($item) => $this->coordinate($user, $item->asset) || $this->inspect($user, $item->asset)
        );
    }
}
