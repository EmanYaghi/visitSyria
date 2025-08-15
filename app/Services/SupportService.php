<?php

namespace App\Services;

use App\Models\Support;
use App\Models\User;
use App\Repositories\SupportRepository;

class SupportService
{
    protected SupportRepository $repo;

    public function __construct(SupportRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Create a support note.
     *
     * @param User $user
     * @param array $data
     * @return Support
     */
    public function createNote(User $user, array $data): Support
    {
        $payload = [
            'user_id' => $user->id,
            'rating' => $data['rating'] ?? 0,
            'comment' => $data['comment'] ?? null,
            'category' => $data['category'] ?? 'app',
        ];


        $support = $this->repo->create($payload);
        $support->load('user.profile','user.adminProfile','user.media');
        return $support;

    }

    public function listSupportsForAdmin(?string $category = null)
    {
        if ($category === 'app' || $category === 'admin') {
            return $this->repo->getByCategory($category);
        }

        // return grouped: both categories as arrays (no paginator)
        return [
            'app' => $this->repo->getByCategory('app'),
            'admin' => $this->repo->getByCategory('admin'),
        ];
    }
}
