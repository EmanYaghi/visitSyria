<?php

namespace App\Services;

use App\Repositories\ArticleRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticleService
{
    protected ArticleRepository $repo;

    public function __construct(ArticleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAllArticles()
    {
        $collection = $this->repo->all();

        if ($collection->isEmpty()) {
            return [];
        }

        return $collection;
    }

    public function getArticleById(int $id): ?Model
    {
        try {
            return $this->repo->find($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function createArticle(Request $request): Model
    {
        $data = $request->validated();
        return $this->repo->create($data);
    }

    public function updateArticle(Request $request, int $id): Model
    {
        $data = $request->validated();
        return $this->repo->update($id, $data);
    }

    public function deleteArticle(int $id): void
    {
        $this->repo->delete($id);
    }

    public function getSimilarArticles(Model $article, int $limit = 6): Collection
    {
        $article->loadMissing('tags');

        $tagIds = $article->tags->pluck('tag_name_id')->unique()->toArray();

        return $this->repo->similarByTags($article->id, $tagIds, $limit);
    }

    public function getArticlesByTag(string $tagName)
    {
        $normalized = mb_strtolower(trim($tagName));
        $allKeywords = ['الكل', 'كل', 'all', '*'];

        if (in_array($normalized, $allKeywords, true)) {
            $collection = $this->repo->all();
            if ($collection->isEmpty()) {
                return [];
            }
            return $collection;
        }

        $collection = $this->repo->getByTagName($tagName);
        if ($collection->isEmpty()) {
            return [];
        }

        return $collection;
    }
}
