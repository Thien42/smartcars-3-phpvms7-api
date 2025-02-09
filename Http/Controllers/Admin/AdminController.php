<?php

namespace Modules\SmartCARS3phpVMS7Api\Http\Controllers\Admin;

use App\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin controller
 */
class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        return view('smartcars3phpvms7api::admin.index');
    }
}
