<?php
namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    public function getAll()
    {
        return Event::all();
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
}
