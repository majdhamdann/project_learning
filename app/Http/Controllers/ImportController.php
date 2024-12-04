<?php

namespace App\Http\Controllers;

use App\Imports\MultiTableImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new MultiTableImport, $request->file('file'));

        return response()->json(['message' => 'File imported successfully!'], 200);
    }
}
