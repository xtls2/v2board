<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\KnowledgeSave;
use App\Http\Requests\Admin\KnowledgeSort;
use App\Models\Knowledge;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class KnowledgeController extends Controller
{
    /**
     * fetch
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId > 0) {
            $knowledge = Knowledge::find($reqId);
            if ($knowledge == null) {
                abort(500, '知识不存在');
            }
            $data = $knowledge;
        } else {
            $data = Knowledge::orderBy(Knowledge::FIELD_SORT, "ASC")->get();
        }

        return response([
            'data' => $data
        ]);
    }

    /**
     * get category
     *
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function category(Request $request)
    {
        return response([
            'data' => array_keys(Knowledge::get()->groupBy(Knowledge::FIELD_CATEGORY)->toArray())
        ]);
    }



    /**
     * save
     *
     * @param KnowledgeSave $request
     * @return ResponseFactory|Response
     */
    public function save(KnowledgeSave $request)
    {
        $reqId = $request->input('id');
        $reqCategory = $request->input('category');
        $reqLanguage = $request->input('language');
        $reqTitle = $request->input('title');
        $reqBody = $request->input('body');

        if ($reqId == null) {
            $knowledge = new Knowledge();
        } else {
            $knowledge = KnowLedge::find($reqId);
            if ($knowledge == null) {
                abort(500, '知识不存在');
            }
        }

        $knowledge->setAttribute(Knowledge::FIELD_CATEGORY, $reqCategory);
        $knowledge->setAttribute(Knowledge::FIELD_LANGUAGE, $reqLanguage);
        $knowledge->setAttribute(Knowledge::FIELD_TITLE, $reqTitle);
        $knowledge->setAttribute(Knowledge::FIELD_BODY, $reqBody);

        if (!$knowledge->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }


    /**
     * show
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function show(Request $request)
    {
        $reqId = (int)$request->input('id');

        if ($reqId <= 0) {
            abort(500, '参数有误');
        }

        /**
         * @var Knowledge $knowledge
         */
        $knowledge = Knowledge::find($reqId);
        if ($knowledge == null) {
            abort(500, '知识不存在');
        }
        $knowledge->setAttribute(Knowledge::FIELD_SHOW, $knowledge->getAttribute(Knowledge::FIELD_SHOW) ?
            Knowledge::SHOW_OFF : Knowledge::SHOW_ON);
        if (!$knowledge->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * sort
     *
     * @param KnowledgeSort $request
     * @return ResponseFactory|Response
     */
    public function sort(KnowledgeSort $request)
    {
        $reqIds = (array)$request->input('knowledge_ids');
        DB::beginTransaction();
        foreach ($reqIds as $k => $id) {
            /**
             * @var Knowledge $knowledge
             */
            $knowledge = Knowledge::find($id);
            if ($knowledge == null) {
                DB::rollBack();
                abort(500, '知识数据异常');
            }

            $knowledge->setAttribute(Knowledge::FIELD_SORT, $k + 1);
            if (!$knowledge->save()) {
                DB::rollBack();
                abort(500, '保存失败');
            }
        }

        DB::commit();
        return response([
            'data' => true
        ]);
    }

    /**
     * drop
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');

        if ($reqId <= 0) {
            abort(500, '参数有误');
        }

        /**
         * @var Knowledge $knowledge
         */
        $knowledge = Knowledge::find($reqId);
        if ($knowledge == null) {
            abort(500, '知识不存在');
        }
        try {
            $knowledge->delete();
        } catch (Exception $e) {
            abort(500, '删除失败');
        }
        return response([
            'data' => true
        ]);
    }
}
