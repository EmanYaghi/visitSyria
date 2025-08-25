<?php

namespace App\Services;

use App\Http\Resources\Auth\AdminProfileResource;
use App\Http\Resources\Auth\AdminResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\companyWithEarningResource;
use App\Models\AdminProfile;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class CompanyService
{
   public function index()
    {
        $user = Auth::user();

        if (!$user || $user->hasRole('client')) {
            $companies = User::role('admin')->with('adminProfile')->get();
            $companies = $companies->filter(function ($company) {
                return $company->adminProfile && $company->status=='accept';
            });
            $companies = CompanyResource::collection($companies);
        } else if ($user->hasRole('super_admin')) {
            $companies = User::role('admin')->with('adminProfile')->get();
            $companies = $companies->filter(function ($company) {
                return $company->adminProfile && $company->status=='accept';
            });
            $companies = companyWithEarningResource::collection($companies->pluck('adminProfile'));
        }
        else{
            $companies=null;
        }

        return [
            'companies' => $companies,
            'message' => 'this is all company',
            'code' => 200
        ];
    }
    public function show($id)
    {
        $company=AdminProfile::find($id);
        if(!$company)
            return [
                'message' => 'not found',
                'code' => 404
            ];
        return [
            'company' =>new companyWithEarningResource( $company),
            'message' => 'this is company that user id is '.$id,
            'code' => 200
        ];
    }
    public function topCompanies()
    {
        if(!Auth::user()->hasRole('super_admin'))
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        $by=request()->query('by');
        if($by=='rating')
            $companies = AdminProfile::orderBy('rating', 'desc')->take(10)->get();
        else if($by=='trip')
            $companies = AdminProfile::orderBy('number_of_trips', 'desc')->take(10)->get();
        else if($by=='earning')
        {
          $companies = User::role('admin')
            ->with('adminProfile', 'trips.bookings')
            ->get()
            ->map(function($user) {
                $user->total_revenue = $user->trips->sum(function($trip) {
                    return $trip->bookings->where('is_paid', true)->sum('price')/5;
                });
                return $user;
            })
            ->sortByDesc('total_revenue')
            ->take(10);
            $companies=$companies->pluck('adminProfile');

        }
        else
            return [
                'message' => 'by must be either trip or rating or earning',
                'code' => 400
            ];
        return [
            'companies' => companyWithEarningResource::collection($companies),
            'message' => 'this is top company',
            'code' => 200
        ];
    }
    public function getCompaniesOnHold()
    {
        if(!Auth::user()->hasRole('super_admin'))
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        $companies = AdminProfile::where('status','في الانتظار')->get();
        return [
            'companies' => AdminResource::collection($companies),
            'message' => 'this is top company',
            'code' => 200
        ];
    }
    public function changeCompanyStatus($request)
    {
        if(!Auth::user()->hasRole('super_admin'))
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        $company = AdminProfile::findOrFail($request['company_id']);
        if($request['status']=='accept')
            {
                $company->user->update(['status'=>$request['status']]);
                $company->update(['status' => 'فعالة']);
            }
        else if ($request['status']=='reject')
        {
            $company->user->update(['status'=>$request['status']]);
        }
        else
            $company->update(['status' => $request['status']]);
        return [
            'company' =>new AdminResource($company),
            'message' => 'this is top company',
            'code' => 200
        ];
    }

    public function getEarning()
    {
        if(!Auth::user()->hasRole('super_admin'))
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        $trip = Booking::whereNotNull('trip_id')->where('is_paid', true)->where('created_at','>=', now()->subDays(7))->sum('price')/20??0;
        $tripLastWeek=Booking::whereNotNull('trip_id')->where('is_paid', true)->where('created_at','<', now()->subDays(7))->where('created_at','>=', now()->subDays(14))->sum('price')/20??0;
        $event=Booking::whereNotNull('event_id')->where('is_paid', true)->where('created_at','>=', now()->subDays(7))->sum('price')/20??0;
        $eventLastWeek=Booking::whereNotNull('event_id')->where('is_paid', true)->where('created_at','<', now()->subDays(7))->where('created_at','>=', now()->subDays(14))->sum('price')/20??0;
        $earnings=$trip+$event;
        $earningsLastWeek=$tripLastWeek+$eventLastWeek;
        $changeFromLastWeek = $earningsLastWeek
            ? (($earnings - $earningsLastWeek) / $earningsLastWeek) * 100
            : 0;
        return [
            "earnings"=>$earnings,
            "changeFromLastWeek"=>$changeFromLastWeek,
            "message" =>'earnings of super admin this weak and compare it with last weak',
            'code'=>200
        ];
    }
   public function getUser()
    {
        if(!Auth::user()->hasRole('super_admin'))
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        $users = User::role('client')
            ->where('created_at','>=', now()->subDays(1))
            ->count();
        $users_last_day = User::role('client')
            ->where('created_at','<', now()->subDays(1))
            ->where('created_at','>=', now()->subDays(2))
            ->count() ?? 0;
        $changeFromLastDay = $users_last_day
            ? (($users - $users_last_day) / $users_last_day) * 100
            : 0;
        return [
            "users" => $users,
            "changeFromLastDay" => $changeFromLastDay,
            "message" => 'number of users today and compare it with yesterday',
            'code' => 200
        ];
    }

    public function getRating()
    {
        $ratings = Rating::where('created_at', '>=', now()->subDays(1))
            ->count() ?? 0;
        $ratingYesterday = Rating::where('created_at', '<', now()->subDays(1))
            ->where('created_at', '>=', now()->subDays(2))
            ->count() ?? 0;
        $changeFromLastDay = $ratingYesterday
            ? (($ratings - $ratingYesterday) / $ratingYesterday) * 100
            : 0;

        return [
            "ratings"=>$ratings,
            "changeFromLastWeek"=>$changeFromLastDay,
            "message" =>'number of user that rate the super admin today and compare it with yesterday',
            'code'=>200
        ];
    }

    public function earningThisYearSA()
    {
        if (!Auth::user()->hasRole('super_admin')) {
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        }
        $monthlyEarnings = [];
        for ($month = 12; $month >0; $month--) {
            $startOfMonth = now()->subMonths($month)->startOfMonth();
            $endOfMonth   = now()->subMonths($month-1)->endOfMonth();
            $trip = Booking::whereNotNull('trip_id')
                ->where('is_paid', true)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('price') / 20;
            $event = Booking::whereNotNull('event_id')
                ->where('is_paid', true)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('price') / 20;
            $monthlyEarnings[12-$month] =($trip + $event)??0;
        }

        return [
            "monthlyEarnings" => $monthlyEarnings,
            "message" => 'earnings of super admin for this year (per month)',
            'code' => 200
        ];
    }
     public function earningThisYearA()
    {
        if (!Auth::user()->hasRole('admin')) {
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        }
        $monthlyEarnings = [];
        for ($month = 12; $month >0; $month--) {
            $startOfMonth = now()->subMonths($month)->startOfMonth();
            $endOfMonth   = now()->subMonths($month-1)->endOfMonth();
            $monthlyEarnings[12-$month] = (
                Booking::where('trip_id',Auth::user()->id)
                ->where('is_paid', true)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('price') / 5
            )??0;
        }

        return [
            "monthlyEarnings" => $monthlyEarnings,
            "message" => 'earnings of admin for this year (per month)',
            'code' => 200
        ];
    }
    public function ratingThisYearA()
    {

        if (!Auth::user()->hasRole('admin')) {
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        }
        $monthlyRatings = [];
        for ($month = 12; $month >0; $month--) {
            $startOfMonth = now()->subMonths($month)->startOfMonth();
            $endOfMonth   = now()->subMonths($month-1)->endOfMonth();
            $monthlyRatings[12-$month] = (
                Rating::where('trip_id',Auth::user()->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count()
            )??0;
        }

        return [
            "monthlyRatings" => $monthlyRatings,
            "message" => 'ratings of admin for this year (per month)',
            'code' => 200
        ];
    }

}
