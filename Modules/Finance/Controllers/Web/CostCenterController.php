<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\CostCenter;

class CostCenterController extends Controller
{
    public function index()
    {
        $costCenters = CostCenter::with(['parent', 'manager'])->orderBy('code')->get();

        return view('finance::cost-centers.index', compact('costCenters'));
    }
}
