<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EventAdminResource extends JsonResource
{
    protected function shouldIncludeStatus($request): bool
    {
        try {
            $path = $request->path();
        } catch (\Throwable $e) {
            $path = '';
        }

        if ($request->query('include_status') === '1') {
            return true;
        }

        if (!empty($path) && Str::startsWith($path, 'admin/')) {
            return true;
        }

        return false;
    }

    protected function toFullUrl(?string $raw): ?string
    {
        if (empty($raw)) return null;
        $raw = trim($raw);
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }
        $raw = preg_replace('#(posts/)+#', 'posts/', $raw);
        $raw = preg_replace('#(storage/)+#', 'storage/', $raw);

        if (Str::startsWith($raw, ['/storage', 'storage'])) {
            return url('/' . ltrim($raw, '/'));
        }

        try {
            $storageUrl = Storage::url($raw);
            if (filter_var($storageUrl, FILTER_VALIDATE_URL)) {
                return $storageUrl;
            }
            return url($storageUrl);
        } catch (\Throwable $e) {
            return url('/storage/' . ltrim($raw, '/'));
        }
    }

    protected function userDisplayName($user): ?string
    {
        if (!$user) return null;

        if (method_exists($user, 'profile') && $user->profile && ($user->profile->first_name || $user->profile->last_name)) {
            $first = trim((string) ($user->profile->first_name ?? ''));
            $last  = trim((string) ($user->profile->last_name ?? ''));
            $name = trim("$first $last");
            if (!empty($name)) return $name;
        }

        if (method_exists($user, 'adminProfile') && $user->adminProfile && ($user->adminProfile->name_of_company ?? null)) {
            return $user->adminProfile->name_of_company;
        }

        if (!empty($user->name)) return $user->name;

        return $user->email ?? null;
    }

    /**
     * امن للوصول إلى علاقة كـ Query Builder أو null إذا غير متاحة.
     */
    protected function relationQuery(string $name)
    {
        if (is_object($this->resource) && method_exists($this->resource, $name)) {
            try {
                return $this->resource->{$name}();
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * استخرج مجموعة (Collection) آمِنة للعلاقة مهما كان شكل المورد (model أو array).
     */
    protected function safeCollectionForRelation(string $relation)
    {
        // 1) إذا العلاقة محمّلة بالفعل على الـ resource
        if ($this->relationLoaded($relation) && $this->{$relation} !== null) {
            return collect($this->{$relation});
        }

        // 2) إذا المورد كائن Eloquent ويملك الميثود -> نخرجها
        if (is_object($this->resource) && method_exists($this->resource, $relation)) {
            try {
                $q = $this->resource->{$relation}();
                if ($q) {
                    return $q->get();
                }
            } catch (\Throwable $e) {
                // نكمل للخطوة التالية
            }
        }

        // 3) إذا المورد مصفوفة أو attribute موجود كمفتاح مصفوفة
        if (is_array($this->resource) && isset($this->resource[$relation]) && is_iterable($this->resource[$relation])) {
            return collect($this->resource[$relation]);
        }

        // 4) fallback
        return collect([]);
    }

    public function toArray($request)
    {
        // جمع الميديا بطريقة آمنة
        $mediaCollection = $this->safeCollectionForRelation('media');
        $mediaUrls = $mediaCollection->map(function ($m) {
            $raw = $m->url ?? ($m['url'] ?? null);
            if (! $raw) return null;
            if (filter_var($raw, FILTER_VALIDATE_URL)) return $raw;
            return Storage::disk('public')->url(ltrim($raw, '/'));
        })->filter()->values()->all();

        // is_saved: افتراضي null ثم نحاول الجواب إذا فيه يوزر
        $isSaved = null;
        if (property_exists($this->resource, 'is_saved')) {
            $isSaved = $this->resource->is_saved;
        } else {
            $user = $request->user('api') ?? $request->user();
            if ($user) {
                $savesQuery = $this->relationQuery('saves');
                if ($savesQuery) {
                    $isSaved = (bool) $savesQuery->where('user_id', $user->id)->whereNotNull('event_id')->exists();
                } else {
                    // ممكن تكون محمّلة كـ collection
                    $savesCol = $this->safeCollectionForRelation('saves');
                    $isSaved = $savesCol->contains('user_id', $user->id);
                }
            } else {
                $isSaved = null;
            }
        }

        // حساب حالة الحدث
        $statusCode = null;
        $statusLabel = null;

        if (!empty($this->status) && $this->status === 'cancelled') {
            $statusCode = 'cancelled';
            $statusLabel = 'تم الإلغاء';
        } else {
            try {
                $now = Carbon::now();
                $start = $this->date ? Carbon::parse($this->date) : null;

                $days = intval($this->duration_days ?? 0);
                $hours = intval($this->duration_hours ?? 0);

                if ($start) {
                    $end = (clone $start)->addDays($days)->addHours($hours);

                    if ($now->lt($start)) {
                        $statusCode = 'not_started';
                        $statusLabel = 'لم تبدأ بعد';
                    } elseif ($now->gt($end)) {
                        $statusCode = 'finished';
                        $statusLabel = 'منتهية';
                    } else {
                        $statusCode = 'ongoing';
                        $statusLabel = 'جارية حالياً';
                    }
                } else {
                    $statusCode = 'not_started';
                    $statusLabel = 'لم تبدأ بعد';
                }
            } catch (\Throwable $e) {
                $statusCode = 'not_started';
                $statusLabel = 'لم تبدأ بعد';
            }
        }

        // owner info
        $owner = $this->user ?? null;
        $userDisplayName = $this->userDisplayName($owner);
        $profilePhotoPath = null;
        if ($owner) {
            if (isset($owner->profile) && !empty($owner->profile->photo)) {
                $profilePhotoPath = $owner->profile->photo;
            } elseif (method_exists($owner, 'media') && $owner->media) {
                $profilePhotoPath = $owner->media->url ?? null;
            }
        }
        $profilePhotoFull = $this->toFullUrl($profilePhotoPath);

        // post image: استخدم أول ميديا إن وُجدت
        $postMedia = $mediaCollection->first();
        $imageFull = $postMedia && !empty(($postMedia->url ?? null)) ? $this->toFullUrl($postMedia->url ?? ($postMedia['url'] ?? null)) : null;

        // tags: امنة
        $tags = [];
        $tagsCol = $this->safeCollectionForRelation('tags');
        if ($tagsCol->isNotEmpty()) {
            // tags قد تكون TagName models أو arrays
            $tags = $tagsCol->map(function($t){
                return $t->body ?? ($t['body'] ?? null);
            })->filter()->values()->all();
        }

        // is_liked / is_saved بالنسبة للمستخدم المصادق (auth)
        $authUser = $request->user();
        if ($authUser) {
            $likesQuery = $this->relationQuery('likes');
            if ($likesQuery) {
                $isLiked = (bool) $likesQuery->where('user_id', $authUser->id)->exists();
            } else {
                $likesCol = $this->safeCollectionForRelation('likes');
                $isLiked = $likesCol->contains('user_id', $authUser->id);
            }

            $savesQuery = $this->relationQuery('saves');
            if ($savesQuery) {
                $isSavedFlag = (bool) $savesQuery->where('user_id', $authUser->id)->exists();
            } else {
                $savesCol = $this->safeCollectionForRelation('saves');
                $isSavedFlag = $savesCol->contains('user_id', $authUser->id);
            }
        } else {
            $isLiked = null;
            $isSavedFlag = null;
        }

        // comments: امنة
        $commentsCol = $this->safeCollectionForRelation('comments');
        if ($commentsCol->isEmpty()) {
            // حاول جلب مع relationQuery إذا ممكن مع eager relations
            $commentsQuery = $this->relationQuery('comments');
            if ($commentsQuery) {
                try {
                    $commentsCol = $commentsQuery->with('user.profile', 'user.media')->get();
                } catch (\Throwable $e) {
                    $commentsCol = collect([]);
                }
            }
        }

        $commentsArr = $commentsCol->map(function ($c) {
            $commentUser = $c->user ?? null;
            $name = $this->userDisplayName($commentUser);
            $photo = null;
            if ($commentUser) {
                if (isset($commentUser->profile) && !empty($commentUser->profile->photo)) {
                    $photo = $commentUser->profile->photo;
                } elseif (method_exists($commentUser, 'media') && $commentUser->media) {
                    $photo = $commentUser->media->url ?? null;
                }
            }

            return [
                'id' => $c->id,
                'user' => [
                    'id' => $commentUser->id ?? null,
                    'name' => $name,
                    'profile_photo' => $this->toFullUrl($photo),
                ],
                'comment' => $c->comment ?? $c->body ?? null,
                'created_at' => optional($c->created_at)->format('Y-m-d'),
            ];
        })->values()->all();

        // بناء الاستجابة الأساسية
        $result = [
            'id' => $this->id,
            'user' => [
                'id' => $owner->id ?? null,
                'name' => $userDisplayName,
                'profile_photo' => $profilePhotoFull,
            ],
            'description' => $this->description,
            'image' => $imageFull,
            'tags' => $tags,
            'is_liked' => $isLiked,
            'is_saved' => $isSavedFlag ?? $isSaved,
            'comments' => $commentsArr,
            'created_at' => optional($this->created_at)->format('Y-m-d'),
        ];

        if ($this->shouldIncludeStatus($request)) {
            $result['status'] = $statusLabel;
            $result['status_code'] = $statusCode;
        }

        return $result;
    }
}
