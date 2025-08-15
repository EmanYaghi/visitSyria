<?php

namespace App\Repositories;

use App\Models\Support;

class SupportRepository
{
    public function create(array $data): Support
    {
        return Support::create($data);
    }

    /**
     * Return collection (no paginator) for given category.
     */
    public function getByCategory(string $category)
    {
        return Support::with([
                'user.profile',
                'user.adminProfile',
                'user.media'
            ])
            ->where('category', $category)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Return all supports grouped by category (no paginator)
     */
    public function getGroupedByCategory()
    {
        return Support::with([
                'user.profile',
                'user.adminProfile',
                'user.media'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('category')
            ->map(function ($group) {
                return $group->values();
            })
            ->toArray();
    }
}
