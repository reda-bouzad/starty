<?php
namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ReportController extends Controller {

    public function lists(Request $request): array|Collection
    {
        return Report::query()->when($request->type,fn($query) => $query->where('type',$request->type))->get();
    }
}
