<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingRepository
{
    protected Setting $model;

    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->get();
    }

    public function find(int $id): ?Setting
    {
        return $this->model->find($id);
    }

    public function findByType(string $type, ?string $category = null): Collection
    {
        $q = $this->model->where('type', $type);
        if ($category !== null) {
            if ($category === 'app') {
                $q->whereIn('category', ['app', 'appandadmin']);
            } elseif ($category === 'admin') {
                $q->whereIn('category', ['admin', 'appandadmin']);
            } elseif ($category === 'appandadmin') {
                $q->where('category', 'appandadmin');
            }
        }
        return $q->get();
    }

    public function findByCategory(string $category): Collection
    {
        if ($category === 'app') {
            return $this->model->whereIn('category', ['app', 'appandadmin'])->get();
        }
        if ($category === 'admin') {
            return $this->model->whereIn('category', ['admin', 'appandadmin'])->get();
        }
        return $this->model->where('category', $category)->get();
    }

    public function findOneByTypeAndCategory(string $type, string $category): ?Setting
    {
        return $this->model->where('type', $type)
                           ->where('category', $category)
                           ->first();
    }

    public function create(array $data): Setting
    {
        return $this->model->create($data);
    }

    public function update(Setting $setting, array $data): Setting
    {
        $setting->fill($data);
        $setting->save();
        return $setting;
    }

    public function delete(Setting $setting): bool
    {
        return (bool) $setting->delete();
    }

    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function countByType(string $type): array
    {
        $appCount = $this->model->where('type', $type)->whereIn('category', ['app', 'appandadmin'])->count();
        $adminCount = $this->model->where('type', $type)->whereIn('category', ['admin', 'appandadmin'])->count();

        return [
            'app' => (int) $appCount,
            'admin' => (int) $adminCount,
        ];
    }
}
