<?php

namespace App\Services;

use App\Repositories\SettingRepository;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingService
{
    protected SettingRepository $repo;

    public function __construct(SettingRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll(): Collection
    {
        return $this->repo->all();
    }

    public function find(int $id): ?Setting
    {
        return $this->repo->findOrFail($id);
    }

    public function findByType(string $type, ?string $category = null): Collection
    {
        return $this->repo->findByType($type, $category);
    }

    public function getByCategory(string $category): Collection
    {
        return $this->repo->findByCategory($category);
    }

    public function create(array $data): Setting
    {
        return $this->repo->create($data);
    }

    public function update(Setting $setting, array $data): Setting
    {
        return $this->repo->update($setting, $data);
    }

    public function delete(Setting $setting): bool
    {
        return $this->repo->delete($setting);
    }

    public function upsertByType(string $type, string $category, array $data): Setting
    {
        $existing = $this->repo->findOneByTypeAndCategory($type, $category);

        if ($existing) {
            return $this->update($existing, $data);
        }

        return $this->create(array_merge(['type' => $type, 'category' => $category], $data));
    }

    /**
     * إرجاع عدد الإعدادات لنوع محدد مفصّل حسب الفئة app و admin.
     *
     * @param string $type
     * @return array{app:int, admin:int}
     */
    public function countByType(string $type): array
    {
        return $this->repo->countByType($type);
    }
}
