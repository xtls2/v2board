<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NoticeSave;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

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
        return response([
            'data' => Notice::orderBy('id', "DESC")->get()
        ]);
    }

    /**
     * save
     *
     * @param NoticeSave $request
     * @return Application|ResponseFactory|Response
     */
    public function save(NoticeSave $request)
    {
        $reqTitle = $request->input('title');
        $reqContent = $request->input('content');
        $reqImgUrl = $request->input('img_url');
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            $notice = new Notice();
        } else {
            $notice = Notice::find($reqId);
            if ($notice === null) {
                abort(500, '公告不存在');
            }
        }

        $notice->setAttribute(Notice::FIELD_TITLE, $reqTitle);
        $notice->setAttribute(Notice::FIELD_CONTENT, $reqContent);
        $notice->setAttribute(Notice::FIELD_IMG_URL, $reqImgUrl);

        if (!$notice->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }


    /**
     * drop
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, '参数错误');
        }
        $notice = Notice::find($reqId);
        if ($notice === null) {
            abort(500, '公告不存在');
        }

        if (!$notice->delete()) {
            abort(500, '删除失败');
        }
        return response([
            'data' => true
        ]);
    }
}
