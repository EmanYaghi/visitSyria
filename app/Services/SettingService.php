<?php

namespace App\Services;

use App\Repositories\SettingRepository;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SettingService
{
    protected SettingRepository $repo;

    public function __construct(SettingRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll(): EloquentCollection
    {
        return $this->repo->all();
    }

    public function find(int $id): ?Setting
    {
        return $this->repo->findOrFail($id);
    }

    public function findByType(string $type, ?string $category = null): EloquentCollection
    {
        return $this->repo->findByType($type, $category);
    }

    public function getByCategory(string $category): EloquentCollection
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

    public function addCounterpart(Setting $setting, array $data): Setting
    {
        $counter = $setting->category === 'admin' ? 'app' : 'admin';
        $existing = $this->repo->findOneByTypeAndCategory($setting->type, $counter);
        $payload = array_merge(['type' => $setting->type, 'category' => $counter], $data);
        if ($existing) {
            return $this->update($existing, $payload);
        }
        return $this->create($payload);
    }

    public function ensureSingleCategory(Setting $setting, string $targetCategory, array $data): Setting
    {
        $payload = array_merge(['category' => $targetCategory], $data);
        $updated = $this->update($setting, $payload);
        $otherCategory = $targetCategory === 'app' ? 'admin' : 'app';
        $other = $this->repo->findOneByTypeAndCategory($updated->type, $otherCategory);
        if ($other && $other->id !== $updated->id) {
            $this->repo->delete($other);
        }
        return $updated;
    }

    public function createBoth(array $data): EloquentCollection
    {
        $created = [];
        $dataApp = array_merge($data, ['category' => 'app']);
        $dataAdmin = array_merge($data, ['category' => 'admin']);
        $created[] = $this->repo->create($dataApp);
        $created[] = $this->repo->create($dataAdmin);
        return new EloquentCollection($created);
    }

    public function upsertBoth(string $type, array $data): EloquentCollection
    {
        $results = [];
        foreach (['app', 'admin'] as $category) {
            $existing = $this->repo->findOneByTypeAndCategory($type, $category);
            $payload = array_merge(['type' => $type, 'category' => $category], $data);
            if ($existing) {
                $results[] = $this->update($existing, $payload);
            } else {
                $results[] = $this->create($payload);
            }
        }
        return new EloquentCollection($results);
    }

    public function upsertByType(string $type, string $category, array $data): Setting
    {
        $existing = $this->repo->findOneByTypeAndCategory($type, $category);
        if ($existing) {
            return $this->update($existing, $data);
        }
        return $this->create(array_merge(['type' => $type, 'category' => $category], $data));
    }

    public function countByType(string $type): array
    {
        return $this->repo->countByType($type);
    }
}
