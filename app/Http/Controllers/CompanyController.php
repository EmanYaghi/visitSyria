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
    }
    public function getEarning()
    {
        $data=[];
        try{
            $data=$this->companyService->getEarning();
            return response()->json(["earnings"=>$data['earnings']??null,"changeFromLastWeek"=>$data['changeFromLastWeek']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function getUser()
    {

        $data=[];
        try{
            $data=$this->companyService->getUser();
            return response()->json(["users"=>$data['users']??null,"changeFromLastDay"=>$data['changeFromLastDay']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function getRating()
    {
        $data=[];
        try{
            $data=$this->companyService->getRating();
            return response()->json(["ratings"=>$data['ratings']??null,"changeFromLastDay"=>$data['changeFromLastDay']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

    public function show($id)
    {
        $data=[];
        try{
            $data=$this->companyService->show($id);
            return response()->json(["company"=>$data['company']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

    public function earningThisYearSA()
    {
        $data=[];
        try{
            $data=$this->companyService->earningThisYearSA();
            return response()->json(["monthlyEarnings"=>$data['monthlyEarnings']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function earningThisYearA()
    {
        $data=[];
        try{
            $data=$this->companyService->earningThisYearA();
            return response()->json(["monthlyEarnings"=>$data['monthlyEarnings']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function ratingThisYearA()
    {
        $data=[];
        try{
            $data=$this->companyService->ratingThisYearA();
            return response()->json(["monthlyRatings"=>$data['monthlyRatings']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
}
