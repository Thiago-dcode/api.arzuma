<?php

namespace App\Traits;

trait httpResponses{


    protected function success($data,$message=null, $status = 200){

            return response()->json([
                'status' => $status,
                'message' =>$message,
                'data'=> $data


            ],$status);

    }
    protected function error($data,$message=null, $status){

        return response()->json([
            'status' => $status,
            'message' =>$message,
            'data'=> $data


        ],$status);

    }

        protected function companyError(){

            
                return $this->error([],'User must has a company activated',401);
            
        


        }
}