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

    /**
     * إرجاع كل السجلات بحسب النوع (قد تكون أكثر من سجل)
     */
    public function findByType(string $type, ?string $category = null): Collection
    {
        $q = $this->model->where('type', $type);
        if ($category !== null) {
            $q->where('category', $category);
        }
        return $q->get();
    }

    /**
     * إرجاع كل السجلات بحسب الفئة category
     */
    public function findByCategory(string $category): Collection
    {
        return $this->model->where('category', $category)->get();
    }

    /**
     * إرجاع سجل واحد بحسب النوع والفئة (مفيد للـ upsert).
     */
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

}
