<?php

namespace App\Repositories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ArticleRepository
{
    protected Article $model;

    public function __construct(Article $article)
    {
        $this->model = $article;
    }

    public function all(): Collection
    {
        return $this->model->with(['media', 'tags.tagName'])->orderBy('created_at', 'desc')->get();
    }

    public function find(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->find($id);
        $model->update($data);
        return $model->refresh();
    }

    public function delete(int $id): void
    {
        $model = $this->find($id);
        $model->delete();
    }

public function similarByTags(int $articleId, array $tagIds, int $limit = 6): \Illuminate\Database\Eloquent\Collection
{
    if (empty($tagIds)) {
        return $this->model->newCollection();
    }

    $ids = \Illuminate\Support\Facades\DB::table('tags')
        ->select('article_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as shared'))
        ->whereIn('tag_name_id', $tagIds)
        ->where('article_id', '!=', $articleId)
        ->groupBy('article_id')
        ->orderByDesc('shared')
        ->limit($limit)
        ->pluck('article_id')
        ->toArray();

    if (empty($ids)) {
        return $this->model->newCollection();
    }

    $articles = $this->model->with(['media', 'tags.tagName'])
        ->whereIn('id', $ids)
        ->get();

    $ordered = [];
    foreach ($ids as $id) {
        $item = $articles->firstWhere('id', $id);
        if ($item) {
            $ordered[] = $item;
        }
    }

    return $this->model->newCollection($ordered);
}

}
