<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PullOutModel;
use App\Models\PullOutBranchModel;
use App\Models\PullOutItemModel;

class FetchController extends Controller
{
    //

    //GETTING THE LAST ID IN THE PULL OUT TBL
    public function fetchLastID(){
        $data = PullOutModel::orderBy('plID', 'desc')->first()->plID;

        return response()->json($data);
    }

    public function fetchLastIDBranch(){
        $data = PullOutBranchModel::orderBy('id', 'desc')->first()->id;

        return response()->json($data);
    }

    public function fetchChain(){
        $data = DB::table('epcbranchmaintenance')
                ->select('chainCode')
                ->distinct()
                ->orderby('chainCode')
                ->get();

        return response()->json($data);
    }

    public function fetchChainName(Request $request){

        // $chainCode = request('chainCode');

        $data = DB::table('epcbranchmaintenance')
                ->select('branchName')
                ->where('chainCode', $request->chainCode)
                ->distinct()
                ->orderby('branchName')
                ->get();

        return response()->json($data);
    }

    public function fetchBrands(Request $request){

        // $chainCode = request('chainCode');
        if($request->companyType == 'EPC')
            $data = DB::table('epcbrandsmaintenance')
                    ->select('brandNames')
                    ->where('id', $request->brandCode)
                    ->distinct()
                    ->orderby('brandNames')
                    ->get();
        else if($request->companyType == 'NBFI')
            $data = DB::table('nbfibrandsmaintenance')
                    ->select('brandNames')
                    ->where('id', $request->brandCode)
                    ->distinct()
                    ->orderby('brandNames')
                    ->get();

        return response()->json($data);
    }

    public function fetchCategory(Request $request){

        $data = DB::table('epcbrandscategory')
                ->select('categoryName')
                ->where('brandName', $request->brandName)
                ->distinct()
                ->orderby('categoryName')
                ->get();

        return response()->json($data);
    }

    public function fetchBranch(){
        $data = DB::table('branch_maintenance')
                ->select('branchCode', 'branchName')
                ->get();

        return response()->json($data);
    }

    public function fetchItems(Request $request){
        $data1 = DB::table('epc_items')
                ->select('ItemNo', 'ItemDescription')
                ->where('ItemNo', 'LIKE', '%'.$request->ItemNo)
                ->get();

        $data2 = DB::table('epc_items')
                ->select('ItemNo', 'ItemDescription')
                ->where('ItemNo', 'LIKE', $request->ItemNo.'%')
                ->get();

        $data = $data1->union($data2);

        return response()->json($data);
    }

    public function compareItemCode(Request $request){
        if($request->companyType == 'EPC')
            $data = DB::table('epc_items')
                    ->select('ItemNo', 'ItemDescription')
                    ->where('ItemNo', '=' ,$request->ItemNo)
                    ->get();

        else if($request->companyType == 'NBFI')
            $data = DB::table('nbfi_items')
                    ->select('ItemNo', 'ItemDescription')
                    ->where('ItemNo', '=' ,$request->ItemNo)
                    ->get();

        return response()->json($data);
    }

    public function fetchItemsNBFI(Request $request){
        $data1 = DB::table('nbfi_items')
                ->select('ItemNo', 'ItemDescription')
                ->where('ItemNo', 'LIKE', '%'.$request->ItemNo)
                ->get();

        $data2 = DB::table('nbfi_items')
                ->select('ItemNo', 'ItemDescription')
                ->where('ItemNo', 'LIKE', $request->ItemNo.'%')
                ->get();

        $data = $data1->union($data2);

        return response()->json($data);
    }

    public function fetchPullOutRequest(){

        // $data = DB::table('pullOutTbl')
        //         ->select('plID', 'chainCode', 'branchName',
        //         'brand', 'transactionType', 'dateTime')
        //         ->distinct()
        //         ->get();
        // $data = DB::table('pullOutTbl as a')
        //         ->join('companyTbl as b', 'a.company', '=', 'b.id')
        //         ->select(DB::raw(('(SELECT shortName FROM companyTbl WHERE id = a.company) as company')),'a.plID', 'a.chainCode', 'a.branchName', 'a.amount',
        //         'a.brand', 'a.transactionType', DB::raw('CAST(a.dateTime AS DATE) as date'),
        //         DB::raw('TIME(dateTime) as time'))
        //         ->distinct()
        //         ->get();

        $data = DB::table('pullOutTbl as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.plID','a.branchName', 'a.transactionType',
                DB::raw('CAST(a.dateTime AS DATE) as date'),
                DB::raw('TIME(dateTime) as time'))
                ->distinct()
                ->where('status', '!=', 'deleted')
                ->orderBy('a.dateTime', 'desc')
                ->get();

        return response()->json($data);
    }

    public function fetchPullOutRequestUnprocessed(Request $request){

        if($request->company == "EPC"){
            $data = DB::table('pullOutBranchTbl as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'))
                ->distinct()
                ->where('status', 'unprocessed')
                ->orderBy('a.dateTime', 'desc')
                ->get();
        }else if($request->company == "NBFI"){
            $data = DB::table('pullOutBranchTblNBFI as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'))
                ->distinct()
                ->where('status', 'unprocessed')
                ->orderBy('a.dateTime', 'desc')
                ->get();
        }

        return response()->json($data);
    }

    public function fetchPullOutRequestUnprocessed2(){

        $data = DB::table('pullOutBranchTbl as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CAST(a.dateTime AS DATE) as date'),
                DB::raw('TIME(dateTime) as time'))
                ->distinct()
                ->where('status', 'unprocessed')
                ->orderBy('a.dateTime', 'desc')
                ->get();

        return response()->json($data);
    }

    public function fetchPullOutRequestItem(Request $request){

        if($request->company == "EPC"){
            $data = DB::table('pullOutItemsTbl')
                    ->select('id','plID', 'boxNumber', 'boxLabel', 'brand', 'itemCode', 'quantity', 'editedBy')
                    ->where('status', '!=', 'deleted')
                    ->where('plID', '=', $request->plID)
                    ->orderBy('boxLabel')
                    ->get();
            return response()->json($data);

        }else if ($request->company == "NBFI") {
            $data = DB::table('pullOutItemsTblNBFI')
                    ->select('id','plID', 'boxNumber', 'boxLabel', 'brand', 'itemCode', 'quantity', 'editedBy')
                    ->where('status', '!=', 'deleted')
                    ->where('plID', '=', $request->plID)
                    ->orderBy('boxLabel')
                    ->get();
            return response()->json($data);

        }



    }

    public function fetchPullOutRequestApproved(Request $request){

        if($request->company == "EPC"){
            $data = DB::table('pullOutBranchTbl as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'),
                'status')
                ->where('status', 'approved')
                ->distinct()
                ->orderBy('a.dateTime', 'desc')
                ->get();
        }else if($request->company == "NBFI"){
            $data = DB::table('pullOutBranchTblNBFI as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'),
                'status')
                ->where('status', 'approved')
                ->distinct()
                ->orderBy('a.dateTime', 'desc')
                ->get();
        }

        return response()->json($data);
    }

    public function fetchPullOutRequestDenied(Request $request){

        if($request->company == "EPC"){
            $data = DB::table('pullOutBranchTbl as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'))
                ->where('status', 'denied')
                ->distinct()
                ->orderBy('a.dateTime', 'desc')
                ->get();
        }else if($request->company == "NBFI"){
            $data = DB::table('pullOutBranchTblNBFI as a')
                ->join('companyTbl as b', 'a.company', '=', 'b.id')
                ->select('a.id as plID','a.branchName', 'a.transactionType',
                DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'))
                ->where('status', 'denied')
                ->distinct()
                ->orderBy('a.dateTime', 'desc')
                ->get();
        }

        return response()->json($data);
    }
    public function fetchItemsRequest(Request $request){

        $data = DB::table('pullOutTbl')
                ->select('id','plID', 'boxNumber', 'boxLabel', 'brand', 'itemCode', 'quantity')
                ->where('status', '!=', 'deleted')
                ->where('plID', '=', $request->plID)
                ->orderBy('boxLabel')
                ->get();

        return response()->json($data);
    }
    public function fetchAllItemsRequest(Request $request){

        $id = $request->input('plID');

        if($request->company == "EPC"){
            $data = DB::table('pullOutBranchTbl as a')
                ->join('pullOutItemsTbl as b', 'a.id', '=', 'b.plID')
                ->select('a.branchName', 'b.brand', 'a.transactionType',
                        DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                        DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'),'b.boxLabel', 'b.itemCode', 'b.quantity',
                        'a.status')
                ->whereIn('a.id', $id)
                ->get();
        }else if($request->company == "NBFI"){
            $data = DB::table('pullOutBranchTblNBFI as a')
                ->join('pullOutItemsTblNBFI as b', 'a.id', '=', 'b.plID')
                ->select('a.branchName', 'b.brand', 'a.transactionType',
                        DB::raw('CONCAT(MONTHNAME(a.dateTime), " ", DATE_FORMAT(a.dateTime, "%d, %Y")) as date'),
                        DB::raw('DATE_FORMAT(a.dateTime, "%h:%i %p") as time'),'b.boxLabel', 'b.itemCode', 'b.quantity',
                        'a.status')
                ->whereIn('a.id', $id)
                ->get();
        }


        return response()->json($data);
    }

    public function users(Request $request){

        $user = DB::table('users')
                ->select('id', 'position', 'name')
                ->where('email', $request->email)
                ->get();

        return response()->json($user);
    }

    public function getPromoName(Request $request){

        $email = "";
        if ($request->company == "EPC"){
            $email = DB::table('pullOutBranchTbl')
                        ->select('promoEmail')
                        ->where('id', $request->id)
                        ->first();
        } else if($request->company == "NBFI"){
            $email = DB::table('pullOutBranchTblNBFI')
                        ->select('promoEmail')
                        ->where('id', $request->id)
                        ->first();
        }


        $name = DB::table('users')
                ->select('name', 'email')
                ->where('email', $email->promoEmail)
                ->first();

        return response()->json($name);
    }

    public function getBranchStatus(Request $request){

        $data = DB::table('pullOutBranchTbl')
                ->select('status')
                ->where('id', $request->id)
                ->get();

        return response()->json($data);
    }

    public function getReasons(Request $request){

        $data = DB::table('reasonMaintenance')
                ->select('id', 'reasonLabel')
                ->where('status', 'Active')
                ->get();

        return response()->json($data);
    }
}
