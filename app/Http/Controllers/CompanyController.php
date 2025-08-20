<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeCompanyStatusRequest;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Throwable;

class CompanyController extends Controller
{
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }
    public function index()
    {
        $data=[];
        try{
            $data=$this->companyService->index();
            return response()->json(["companies"=>$data['companies']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function topCompanies()
    {
        $data=[];
        try{
            $data=$this->companyService->topCompanies();
            return response()->json(["companies"=>$data['companies']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function getCompaniesOnHold()
    {
        $data=[];
        try{
            $data=$this->companyService->getCompaniesOnHold();
            return response()->json(["companies"=>$data['companies']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function changeCompanyStatus(ChangeCompanyStatusRequest $request)
    {
        $data=[];
        try{
            $data=$this->companyService->changeCompanyStatus($request->validated());
            return response()->json(["company"=>$data['company']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }/*
    getEarning()
    {
        allearning
        nspa mooawia lziada aw neqsan last week
    }
    getUser()
    {
        allusers
        nspa mooawia lziada aw neqsan yesterday
    }
    getRating()
    {

    }*/
}
