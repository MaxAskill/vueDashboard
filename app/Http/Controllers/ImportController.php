<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\BranchImportModel;
use App\Imports\ItemsImportModel;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    //

    public function branchImport(Request $request){

        $file = $request->file('file');
        $filePath = $file->getRealPath(); // Extract the file path

        Excel::import(new BranchImportModel, $filePath, null, \Maatwebsite\Excel\Excel::CSV);

        return response()->json(['message' => 'success'], 200);
    }

    public function itemsImport(Request $request){

        $file = $request->file('file');
        $filePath = $file->getRealPath(); // Extract the file path

        Excel::import(new ItemsImportModel, $filePath, null, \Maatwebsite\Excel\Excel::CSV);

        return response()->json(['message' => 'success'], 200);
    }
}
