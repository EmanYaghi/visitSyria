<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    public function getAll()
    {
        // return collection of Event models, eager-load media here
        return Event::with('media')->get();
    }

    public function findWithMedia($id)
    {
        return Event::with('media')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Event::create($data);
    }

    public function update(Event $event, array $data)
    {
        $event->update($data);
        $event->refresh();
        return $event;
    }

    public function delete(Event $event)
    {
        return $event->delete();
    }

    public function find($id)
    {
        return Event::findOrFail($id);
    }

    public function updateStatus(Event $event, string $status)
    {
        // Only allow 'active' or 'cancelled' to be persisted
        $status = $status === 'cancelled' ? 'cancelled' : 'active';
        $event->update(['status' => $status]);
        $event->refresh();
        return $event;
    }
}
