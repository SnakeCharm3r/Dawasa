<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EntityAccessService
{
    public function apply(Builder $query, Request $request, User $user, ?string $relation = null): Builder
    {
        $entityId = $this->entityId($request, $user);

        if ($entityId === null) {
            return $query;
        }

        if ($relation) {
            return $query->whereHas($relation, fn (Builder $related) => $related->where('business_entity_id', $entityId));
        }

        return $query->where('business_entity_id', $entityId);
    }

    public function entityId(Request $request, User $user): ?int
    {
        if ($user->hasAnyRole(['ceo', 'super_admin'])) {
            return $request->filled('business_entity_id')
                ? (int) $request->input('business_entity_id')
                : null;
        }

        return $user->department?->business_entity_id;
    }

    public function canAccess(User $user, ?int $entityId): bool
    {
        if ($user->hasAnyRole(['ceo', 'super_admin'])) {
            return true;
        }

        return $entityId !== null
            && (int) $user->department?->business_entity_id === $entityId;
    }
}
