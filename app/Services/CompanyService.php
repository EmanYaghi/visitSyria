<?php

namespace App\Services;

use App\Http\Resources\Auth\AdminProfileResource;
use App\Http\Resources\CompanyResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CompanyService
{
   public function index()
    {
        $companies = User::role('admin')->with('adminProfile')->get();
        $user = Auth::user();

        if (!$user || $user->hasRole('client')) {
            $companies = $companies->filter(function ($company) {
                return $company->adminProfile && $company->adminProfile->status === 'فعالة';
            });
            $companies = CompanyResource::collection($companies);
        } else if ($user->hasRole('super_admin')) {
            $companies = AdminProfileResource::collection($companies);
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


}
