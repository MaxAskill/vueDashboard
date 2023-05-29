<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FetchAdminController extends Controller
{
    //User Maintenance
    public function fetchUsers(){

        $data = DB::table('users')
                ->select('id','name', 'email', 'position', 'status',
                DB::raw('CONCAT(MONTHNAME(created_at), " ", DATE_FORMAT(created_at, "%d, %Y")) as creation_date'),
                DB::raw('DATE_FORMAT(created_at, "%h:%i %p") as creation_time'),
                DB::raw('CONCAT(MONTHNAME(updated_at), " ", DATE_FORMAT(updated_at, "%d, %Y")) as updated_date'),
                DB::raw('DATE_FORMAT(updated_at, "%h:%i %p") as updated_time'),)
                ->get();

        return response()->json($data);
    }

    //EPC Brands Maintenance
    public function fetchEPCBrandsMaintenance(){

        $data = DB::table('epcbrandsmaintenance')
                ->select('id', 'brandNames', 'status')
                ->get();

        return response()->json($data);
    }
    //EPC Items Maintenance
    public function epcItems(){

        $data = DB::table('epc_items as a')
                ->join('epcbrandsmaintenance as b', DB::raw('CAST(a.Brand AS UNSIGNED)'), '=', DB::raw('CAST(b.id AS UNSIGNED)'))
                ->select('a.ItemNo', 'a.ItemDescription', 'b.brandNames', 'a.Department', 'a.SubDepartment', 'a.Category', 'a.SubCategory')
                ->get();

        // $data2 = DB::table('nbfi_items as a')
        //         ->join('nbfibrandsmaintenance as b', DB::raw('CAST(a.Brand AS UNSIGNED)'), '=', DB::raw('CAST(b.id AS UNSIGNED)'))
        //         ->select('a.ItemNo', 'a.ItemDescription', 'b.brandNames', 'a.Department', 'a.SubDepartment', 'a.Category', 'a.SubCategory')
        //         ->get();

        // $data = $data1->union($data2);

        return response()->json($data);
    }

    //EPC Branch Maintenance
    public function fetchBranchMaintenance(){

        $data = DB::table('epcbranchmaintenance')
                ->select('id', 'chainCode', 'branchCode', 'branchName', 'status')
                ->distinct()
                ->orderby('branchName')
                ->get();

        foreach ($data as $item) {
                $chainCode = str_replace('ï¿½', '-', $item->chainCode);
                $item->chainCode = $chainCode;
                $branchName = str_replace('ï¿½', 'Ñ', $item->branchName);
                $item->branchName = $branchName;
        }

        return response()->json($data);
    }

    //EPC Brand Maintenance
    public function fetchBrandMaintenance(){

        $data = DB::table('epcbrandsmaintenance')
                        ->select('id', 'brandNames',
                                DB::raw('CASE WHEN status = "Y" THEN "Active" ELSE "Inactive" END AS status'))
                        ->distinct()
                        ->orderBy('id')
                        ->get();


        return response()->json($data);
    }

    //EPC Pull Out Branch Maintenance
    public function fetchPullOutBranch(){

        $data = DB::table('pullOutBranchTbl')
                ->select('id', 'chainCode', 'company', 'branchName', 'transactionType',
                        'status', DB::raw('CONCAT(MONTHNAME(dateTime), " ", DATE_FORMAT(dateTime, "%d, %Y")) as creation_date'),
                        DB::raw('CONCAT(MONTHNAME(updated_at), " ", DATE_FORMAT(updated_at, "%d, %Y")) as updated_date'), 'editedBy', 'promoEmail')
                ->get();

        return response()->json($data);
    }

    //EPC Pull Out Items Maintenance
    public function fetchPullOutItems(){

        $data = DB::table('pullOutItemsTbl')
                ->select('id', 'plID', 'boxNumber', 'boxLabel', 'itemCode', 'brand',
                        'quantity', 'amount', 'status', 'dateTime', 'updated_at', 'editedBy')
                ->get();

        return response()->json($data);
    }

    //EPC Box Label Maintenance
    public function fetchBoxLabel(){

        $data = DB::table('boxLabelMaintenance')
                ->select('id', 'boxLabel', 'dateTime', 'status')
                ->get();

        return response()->json($data);
    }

    //EPC Reason Maintenance
    public function fetchReasonLabel(){

        $data = DB::table('reasonMaintenance')
                ->select('id', 'reasonLabel', 'dateTime', 'status')
                ->get();

        return response()->json($data);
    }

    //Driver Maintenance
    public function fetchDriverMaintenance(){

        $data = DB::table('driverMaintenance')
                ->select('id', 'name', 'position', 'dateTime', 'status')
                ->get();

        return response()->json($data);
    }

    //Transaction Logs
    public function fetchTransactionLogs(){

        $data = DB::table('transactionLogTbl as a')
                        ->join('users as b', 'a.userID', '=', 'b.id')
                        ->select('a.id', DB::raw('CONCAT(MONTHNAME(dateTime), " ", DATE_FORMAT(dateTime, "%d, %Y")) as creation_date'),
                        DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'),
                                'a.action_type', 'b.name', 'table_affected', 'old_data', 'new_data')
                        ->orderby('a.dateTime', 'desc')
                        ->get();

        return response()->json($data);
    }
}
