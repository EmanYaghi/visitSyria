<?php

namespace App\Repositories;

use App\Models\Support;
use Illuminate\Support\Facades\DB;

class SupportRepository
{
    public function create(array $data): Support
    {
        return Support::create($data);
    }

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

public function getMonthlyRatingsCounts(?int $year = null): array
{
    $year = $year ?: date('Y');
    $rows = DB::table('supports')
        ->selectRaw('MONTH(created_at) as month, COUNT(*) as cnt')
        ->whereYear('created_at', $year)
        ->whereNotNull('rating')
        ->groupByRaw('MONTH(created_at)')
        ->pluck('cnt', 'month')
        ->toArray();
    $result = [];
    for ($m = 1; $m <= 12; $m++) {
        $result[] = isset($rows[$m]) ? (int)$rows[$m] : 0;
    }
    return $result;
}

}
