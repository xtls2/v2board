<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\Knowledge;
use Illuminate\Http\Response;

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
        $reqId = (int)$request->input(['id']);
        $sessionId = $request->session()->get('id');

        if ($reqId > 0) {

            /**
             * @var Knowledge $knowledge
             */
            $knowledge = Knowledge::find($reqId);
            if ($knowledge == null || $knowledge->getAttribute(Knowledge::FIELD_SHOW) == Knowledge::SHOW_OFF) {
                abort(500, __('Article does not exist'));
            }

            /**
             * @var User $user
             */
            $user = User::find($sessionId);
            if ($user == null) {
                abort(500, __('The user does not exist'));
            }

            $knowBody = $knowledge->getAttribute(Knowledge::FIELD_BODY);
            if ($user->isAvailable()) {
                $appleId = config('v2board.apple_id');
                $appleIdPassword = config('v2board.apple_id_password');
            } else {
                $appleId = __('No active subscription. Unable to use our provided Apple ID');
                $appleIdPassword = __('No active subscription. Unable to use our provided Apple ID');
                $this->formatAccessData($knowBody);
            }

            $subscribeUrl = config('v2board.app_url', env('APP_URL'));
            $subscribeUrls = explode(',', config('v2board.subscribe_url'));
            if ($subscribeUrls) {
                $subscribeUrl = $subscribeUrls[rand(0, count($subscribeUrls) - 1)];
            }
            $subscribeUrl = "{$subscribeUrl}/api/v1/client/subscribe?token={$user['token']}";

            $knowBody = str_replace('{{siteName}}', config('v2board.app_name', 'V2Board'), $knowBody);
            $knowBody = str_replace('{{subscribeUrl}}', $subscribeUrl, $knowBody);
            $knowBody = str_replace('{{urlEncodeSubscribeUrl}}', urlencode($subscribeUrl), $knowBody);
            $knowBody = str_replace('{{base64EncodeSubscribeUrl}}', base64_encode($subscribeUrl), $knowBody);
            $knowBody = str_replace(
                '{{safeBase64SubscribeUrl}}',
                str_replace(
                    array('+', '/', '='),
                    array('-', '_', ''),
                    base64_encode($subscribeUrl)
                ),
                $knowBody
            );
            $knowledge->setAttribute(Knowledge::FIELD_BODY, $knowBody);
            $data = $knowledge;
        } else {
            $data = Knowledge::select([Knowledge::FIELD_ID, Knowledge::FIELD_CATEGORY, Knowledge::FIELD_TITLE, Knowledge::FIELD_UPDATED_AT])
                ->where(Knowledge::FIELD_LANGUAGE, $request->input(Knowledge::FIELD_LANGUAGE))
                ->where(Knowledge::FIELD_SHOW, Knowledge::SHOW_ON)
                ->orderBy(Knowledge::FIELD_SORT, "ASC")
                ->get()
                ->groupBy(Knowledge::FIELD_CATEGORY);
        }

        return response([
            'data' => $data
        ]);
    }

    /**
     * format data
     *
     * @param string $body
     */
    private function formatAccessData(string &$body)
    {
        function getBetween($input, $start, $end)
        {
            return substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
        }

        $accessData = getBetween($body, '<!--access start-->', '<!--access end-->');
        if ($accessData) {
            $body = str_replace($accessData, '<div class="v2board-no-access">'. __('You must have a valid subscription to view content in this area') .'</div>', $body);
        }
    }
}
