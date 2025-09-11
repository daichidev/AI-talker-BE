<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with('user')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('report_text', 'like', "%{$search}%")
                  ->orWhere('report_type', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(20);

        return view('admin.reports.index', compact('reports'));
    }
}


