<?php

namespace App\Http\Controllers;

use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Throwable;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;
    public function __construct(FeedbackService $feedbackService) {
        $this->feedbackService = $feedbackService;
    }
    public function setSave($id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->setSave($id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function deleteSave($id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->deleteSave($id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
     public function getSaves()
    {
        $data=[];
        try{
            $data=$this->feedbackService->getSaves();
            return response()->json(["saves"=>$data['saves']]);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function setRating(Request $request,$id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->setRating($request->validate(['rating_value'=>'required']),$id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function deleteRating($id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->deleteRating($id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function setComment(Request $request,$id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->setComment($request->validate(['body'=>'required|string']),$id);
            return response()->json(["comment"=>$data['comment'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function deleteComment($id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->deleteComment($id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

     public function mySavedTrips()
    {
        $data=[];
        try{
            $data=$this->feedbackService->mySavedTrips();
            return response()->json(["trips"=>$data['trips'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function search()
    {
        $data=[];
        try{
            $type=request()->query('type');
            $word=request()->query('word');
            $data=$this->feedbackService->search($type,$word);
            return response()->json(["results"=>$data['results'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

}
