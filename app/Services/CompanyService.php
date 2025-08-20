<?php

namespace App\Services;

use App\Http\Resources\Auth\AdminProfileResource;
use App\Http\Resources\Auth\AdminResource;
use App\Http\Resources\CompanyResource;
use App\Models\AdminProfile;
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
                return $company->adminProfile && $company->adminProfile->status === 'فعالة';
            });
            $companies = CompanyResource::collection($companies);
        } else if ($user->hasRole('super_admin')) {
            $companies=AdminProfile::all();
            $companies = AdminResource::collection($companies);
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
            'companies' => AdminResource::collection($companies),
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
        $user = User::findOrFail($request['company_id']);
        if($request['status']=='accept'||$request['status']=='reject')
            {
                $user->update(['status'=>$request['status']]);
                $user->adminProfile->update(['status' => 'فعالة']);
            }
        else
            $user->adminProfile->update(['status' => $request['status']]);
        $company=$user->adminProfile;
        return [
            'company' =>new AdminResource($company),
            'message' => 'this is top company',
            'code' => 200
        ];
    }
}
