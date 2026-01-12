<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Service\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function changeLang(Request $request)
    {
        $lang = $request->post('lang');
        Site::setLang($lang);

        /*dd(Site::lang());*/


        return response()->json(['success' => true]);
    }
}
