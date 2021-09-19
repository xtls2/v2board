<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\Notice;
use Illuminate\Http\Response;

class NoticeController extends Controller
{
    /**
     * fetch
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $reqCurrent = $request->input('current') ? $request->input('current') : 1;
        $reqPageSize = 5;
        $notices = Notice::orderBy('created_at', "DESC");
        $total = $notices->count();
        $data = $notices->forPage($reqCurrent, $reqPageSize)->get();
        return response([
            'data' => $data,
            'total' => $total
        ]);
    }
}
