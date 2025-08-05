<?php

namespace App\Traits;

use App\Models\Trip;
use App\Services\RouteService;

trait TripPath
{
    protected $rs;

    protected function initRouteService()
    {
        if (!$this->rs) {
            $this->rs = app(RouteService::class);
        }
    }

    public function getRoute(Trip $trip)
    {
        $this->initRouteService();

        $coordinates = [];

        foreach ($trip->timelines as $timeline) {
            foreach ($timeline->sections as $section) {
                if (!empty($section->latitude) && !empty($section->longitude)) {
                    $coordinates[] = [
                        (float) $section->longitude,
                        (float) $section->latitude
                    ];
                }
            }
        }
        if (empty($coordinates)||count($coordinates)<2) {
            return [];
        }
        return $this->rs->getDrivingRoute($coordinates);
    }
}
