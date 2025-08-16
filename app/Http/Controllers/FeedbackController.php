<?php

namespace App\Http\Controllers;

use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Throwable;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function setSave($id)
    {
        try {
            $data = $this->feedbackService->setSave($id);
            return response()->json(['message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function deleteSave($id)
    {
        try {
            $data = $this->feedbackService->deleteSave($id);
            return response()->json(['message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getSaves()
    {
        try {
            $data = $this->feedbackService->getSaves();
            return response()->json(['saves' => $data['saves']], 200);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function setRating(Request $request, $id)
    {
        try {
            $data = $this->feedbackService->setRating($request->validate(['rating_value' => 'required']), $id);
            return response()->json(['message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function deleteRating($id)
    {
        try {
            $data = $this->feedbackService->deleteRating($id);
            return response()->json(['message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function setComment(Request $request, $id)
    {
        try {
            $data = $this->feedbackService->setComment($request->validate(['body' => 'required|string']), $id);
            return response()->json(['comment' => $data['comment'], 'message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function deleteComment($id)
    {
        try {
            $data = $this->feedbackService->deleteComment($id);
            return response()->json(['message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function mySavedTrips()
    {
        try {
            $data = $this->feedbackService->mySavedTrips();
            return response()->json(['trips' => $data['trips'], 'message' => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
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

public function getFeedback(Request $request, $id)
{
    try {
        $data = $this->feedbackService->getFeedback($id);
        if (isset($data['code']) && $data['code'] !== 200) {
            return response()->json(['message' => $data['message']], $data['code']);
        }
        return response()->json(['comments' => $data['comments']], 200);
    } catch (\Throwable $th) {
        return response()->json(['message' => $th->getMessage()], 500);
    }
}

}
