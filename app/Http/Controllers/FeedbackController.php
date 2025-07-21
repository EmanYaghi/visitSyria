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
            $data=$this->feedbackService->setRating($request->validate());
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
            $data=$this->feedbackService->setComment($request->validate());
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

     public function getFeedback($id)
    {
        $data=[];
        try{
            $data=$this->feedbackService->getFeedback();
            return response()->json(["feedback"=>$data['feedback'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
}
