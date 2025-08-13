<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Services\ArticleService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function index(Request $request)
    {
        $articles = $this->articleService->getAllArticles();

        if (is_array($articles) && empty($articles)) {
            return response()->json([], 200);
        }

        $user = $request->user('api');

        if ($user && $articles instanceof \Illuminate\Database\Eloquent\Collection) {
            $articles->load(['saves' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('article_id');
            }]);
        }

        return ArticleResource::collection($articles);
    }

    public function show(Request $request, $id)
    {
        $article = $this->articleService->getArticleById($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $user = $request->user('api');

        if ($user) {
            $article->loadMissing([
                'media',
                'saves' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)->whereNotNull('article_id');
                },
                'tags.tagName'
            ]);
        } else {
            $article->loadMissing(['media', 'tags.tagName']);
        }

        $similar = $this->articleService->getSimilarArticles($article, 6);

        $articleData = (new ArticleResource($article))->resolve();
        $similarData = ArticleResource::collection($similar)->resolve();

        return response()->json([
            'data' => $articleData,
            'similar' => $similarData,
        ], 200);
    }

    public function store(StoreArticleRequest $request)
    {
        DB::beginTransaction();
        try {
            $article = $this->articleService->createArticle($request);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('articles', 'public');
                $article->media()->create(['url' => $path]);
            }

            if ($request->filled('tags')) {
                $names = $request->input('tags', []);
                $names = array_map('trim', $names);
                $names = array_values(array_filter(array_unique($names)));
                $names = array_slice($names, 0, 5);

                $user = $request->user('api');
                $rows = [];
                foreach ($names as $name) {
                    $tagName = \App\Models\TagName::firstOrCreate(
                        ['body' => $name, 'follow_to' => 'article']
                    );
                    $rows[] = [
                        'tag_name_id' => $tagName->id,
                    ];
                }
                if (! empty($rows)) {
                    $article->tags()->createMany($rows);
                }
            }

            DB::commit();

            $article->load(['media', 'tags.tagName']);

            return (new ArticleResource($article))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(UpdateArticleRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $article = $this->articleService->updateArticle($request, $id);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('articles', 'public');

                $media = $article->media;
                if ($media) {
                    $oldPath = $this->storagePathFromUrl($media->url);
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                    $media->update(['url' => $path]);
                } else {
                    $article->media()->create(['url' => $path]);
                }
            }

            if ($request->filled('tags')) {
                $names = $request->input('tags', []);
                $names = array_map('trim', $names);
                $names = array_values(array_filter(array_unique($names)));
                $names = array_slice($names, 0, 5);

                $user = $request->user('api');

                $article->tags()->delete();

                $rows = [];
                foreach ($names as $name) {
                    $tagName = \App\Models\TagName::firstOrCreate(
                        ['body' => $name, 'follow_to' => 'article']
                    );
                    $rows[] = [
                        'tag_name_id' => $tagName->id,
                    ];
                }
                if (! empty($rows)) {
                    $article->tags()->createMany($rows);
                }
            }

            DB::commit();

            $article->load(['media', 'tags.tagName']);

            return new ArticleResource($article);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Article not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $article = $this->articleService->getArticleById($id);
            if (! $article) {
                return response()->json(['message' => 'Article not found'], 404);
            }

            DB::beginTransaction();

            $media = $article->media;
            if ($media) {
                $oldPath = $this->storagePathFromUrl($media->url);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $media->delete();
            }

            $this->articleService->deleteArticle($id);

            DB::commit();
            return response()->json(['message' => 'Article deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    protected function storagePathFromUrl(?string $url): ?string
    {
        if (! $url) return null;

        $path = parse_url($url, PHP_URL_PATH);
        if (! $path) return null;

        $prefix = '/storage/';
        if (str_starts_with($path, $prefix)) {
            return ltrim(substr($path, strlen($prefix)), '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }

        return null;
    }

    public function similar(Request $request, $id)
    {
        $article = $this->articleService->getArticleById($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $limit = (int) $request->query('limit', 6);
        $similar = $this->articleService->getSimilarArticles($article, $limit);

        $user = $request->user('api');
        if ($user && $similar instanceof \Illuminate\Database\Eloquent\Collection) {
            $similar->load(['saves' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('article_id');
            }]);
        }

        return ArticleResource::collection($similar);
    }

    public function getByTag(Request $request, string $tag)
    {
        $articles = $this->articleService->getArticlesByTag($tag);

        if (is_array($articles) && empty($articles)) {
            return response()->json([], 200);
        }

        $user = $request->user('api');

        if ($user && $articles instanceof \Illuminate\Database\Eloquent\Collection) {
            $articles->load(['saves' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('article_id');
            }]);
        }

        return ArticleResource::collection($articles);
    }
}
