<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PullOutModel;
use App\Models\PullOutBranchModel;
use App\Models\PullOutItemModel;
use App\Models\PullOutBranchModelNBFI;
use App\Models\PullOutItemModelNBFI;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;
use PdfReport;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailNotify;
use App\Models\TransactionModel;
use App\Models\EpcBranchModel;
use App\Models\EpcBrandModel;
use App\Models\EpcDriverModel;
use App\Models\EpcReasonModel;
use App\Http\Controllers\BrevoSMService;

class PostController extends Controller
{

    public function checkAccStatus(Request $request){

        $status = DB::table('users')
                    ->select('status')
                    ->where('email', $request->email)
                    ->get();

        return response()->json($status);
    }

    public function postPullOutRequest(Request $request){

        //GETTING THE EFFECTIVE PRICE OF THE SPECIFIC ITEM
        $price = DB::table('epc_items')
                    ->select('EffectivePrice')
                    ->where('ItemNo', '=', $request->itemCode)
                    ->first();

        //COMPUTATION TOTAL AMOUNT
        $amount = floatval($price->EffectivePrice) * floatval($request->quantity);

        //CONDITION WITH THE COMPANY
        if ($request->chainCode == "RDS"){
            $company = 5; //AHLC
        }else{
            $company = 4; //EPC
        }

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        //PUTTING IT INTO THE OBJECT FOR THE MODEL
        $input = new PullOutModel();
        $input->plID = $request->id;
        $input->chainCode = $request->chainCode;
        $input->company = $company;
        $input->branchName = $request->branchName;
        $input->brand = $request->brand;
        $input->transactionType = $request->transactionType;
        $input->boxNumber = $request->boxNumber;
        $input->boxLabel = $request->boxLabel;
        $input->itemCode = $request->itemCode;
        $input->quantity = $request->quantity;
        $input->amount = $amount;
        $input->status = 'unprocessed';
        $input->dateTime = $date;

        //SAVING
        $input->save();
        return response()->json($input);

    }

    public function generatePDF(Request $request){

        //GET THE ID
        $id = $request->input('plID');

        if($request->company == "EPC"){
            //GET THE ONLY NEEDED SINGLE DATA AND WILL BE USE THE QUANTITY AND TOTAL AMOUNT
            $tempdata = DB::table('pullOutBranchTbl as a')
                            ->join('pullOutItemsTbl as b', 'a.id', '=', 'b.plID')
                            ->join('epc_items as c', 'b.itemCode', '=', 'c.ItemNo')
                            ->select('a.chainCode', 'a.branchName', 'a.transactionType',
                            DB::raw('CAST(a.dateTime AS DATE) as date'), 'b.quantity',
                            'b.amount', 'b.status', 'b.boxLabel',
                            'c.ItemDescription as itemDescription', 'b.itemCode', 'b.brand')
                            ->where('a.id', $id)
                            ->get();

            $emaildata = DB::table('pullOutBranchTbl as a')
                        ->join('pullOutItemsTbl as b', 'a.id', '=', 'b.plID')
                        ->join('epc_items as c', 'b.itemCode', '=', 'c.ItemNo')
                        ->select('a.chainCode', 'a.branchName', 'a.transactionType',
                        DB::raw('CAST(a.dateTime AS DATE) as date'), 'b.quantity',
                        'b.amount', 'b.status', 'b.boxLabel',
                        'c.ItemDescription as itemDescription', 'b.itemCode', 'b.brand')
                        ->where('a.id', $id)
                        ->where('b.status', 'edited')
                        ->get();

            //GETTING THE ONLY BOX LABEL GROUP AND THE SUB TOTAL OF QUANTITY AND AMOUNT
            $data = DB::table('pullOutItemsTbl as a')
                            ->select('a.brand',
                                DB::raw('SUM(a.quantity) as quantity_total'),
                                DB::raw('SUM(a.amount) as amount_total')
                            )
                            ->where('a.plID', $id)
                            ->groupBy('a.brand')
                            ->get();
        } else if($request->company == "NBFI"){
            //GET THE ONLY NEEDED SINGLE DATA AND WILL BE USE THE QUANTITY AND TOTAL AMOUNT
            $tempdata = DB::table('pullOutBranchTblNBFI as a')
                            ->join('pullOutItemsTblNBFI as b', 'a.id', '=', 'b.plID')
                            ->join('nbfi_items as c', 'b.itemCode', '=', 'c.ItemNo')
                            ->select('a.chainCode', 'a.branchName', 'a.transactionType',
                            DB::raw('CAST(a.dateTime AS DATE) as date'), 'b.quantity',
                            'b.amount', 'b.status', 'b.boxLabel',
                            'c.ItemDescription as itemDescription', 'b.itemCode', 'b.brand')
                            ->where('a.id', $id)
                            ->get();

            $emaildata = DB::table('pullOutBranchTblNBFI as a')
                            ->join('pullOutItemsTblNBFI as b', 'a.id', '=', 'b.plID')
                            ->join('nbfi_items as c', 'b.itemCode', '=', 'c.ItemNo')
                            ->select('a.chainCode', 'a.branchName', 'a.transactionType',
                            DB::raw('CAST(a.dateTime AS DATE) as date'), 'b.quantity',
                            'b.amount', 'b.status', 'b.boxLabel',
                            'c.ItemDescription as itemDescription', 'b.itemCode', 'b.brand')
                            ->where('a.id', $id)
                            ->where('b.status', 'edited')
                            ->get();
            //GETTING THE ONLY BOX LABEL GROUP AND THE SUB TOTAL OF QUANTITY AND AMOUNT
            $data = DB::table('pullOutItemsTblNBFI as a')
                            ->select('a.brand',
                                DB::raw('SUM(a.quantity) as quantity_total'),
                                DB::raw('SUM(a.amount) as amount_total')
                            )
                            ->where('a.plID', $id)
                            ->groupBy('a.brand')
                            ->get();
        }

        //GETTING THE DRIVERS
        $drivers = DB::table('driverMaintenance')
                    ->select('name', 'position')
                    ->where('status', 'Active')
                    ->get();

        //GETTING THE BOX COUNT
        $box = DB::table('pullOutItemsTbl')
                ->select('boxLabel')
                ->where('plID', $id)
                ->groupBy('boxLabel')
                ->get();

        $boxCount = $box->count(); //BOX COUNT

        $length = $tempdata->count(); // LENGTH OF THE DATA

        $totalQuantity = 0;
        $totalAmount = 0;

        $statusCount = false;
        //COMPUTATION FOR THE TOTAL OF QUANTITY AND AMOUNT
        for ($i = 0; $i < $length; $i++){
            $totalQuantity = $totalQuantity + $tempdata[$i]->quantity;
            $totalAmount = $totalAmount + $tempdata[$i]->amount;

            if($tempdata[$i]->status == "edited"){
                $statusCount = true;
            }

        }

        $tempdate = $tempdata[0]->date;
        //FORMATTING DATE
        $formattedDateStart = date("F j, Y", strtotime($request->dateStart));
        $formattedDateEnd = date("F j, Y", strtotime($request->dateEnd));
        $formattedDate = date("F j, Y", strtotime($tempdate));

        //FORMATTING AMOUNT
        $formattedAmount = number_format($totalAmount, 2, '.', ',');

        //TRANSFERRING INTO ARRAY TO BE EASY ACCESS ON SINGLE DATA
        $info = [
            'name' => $request->name,
            'boxCount' => $boxCount,
            'totalQuantity' => $totalQuantity,
            'totalAmount' => $formattedAmount,
            'date' => $formattedDate,
            'branchName' => $tempdata[0]->branchName,
            'chainCode' => $tempdata[0]->chainCode,
            'dateStart' => $formattedDateStart,
            'dateEnd' => $formattedDateEnd,
            'company' => $request->company
        ];

        //CONVERTING IT INTO STRING FOR THE FILE NAME
        $string = strval($tempdata[0]->branchName);
        $today = date('Y-m-d');

        //CONDITION OF PDF TO BE SHOW
        if($tempdata[0]->transactionType == "CPO - BranchDisposal"){
            $file = "itemDisposal";
        }
        else if($tempdata[0]->transactionType == "CPO - Warehouse(DC)"){
            $file = "directPullOut";
        }
        else{
            $file = "pullOutLetter";
        }

        //LOADING IT INTO THE PDF
        $pdf = PDF::loadView($file, array('data' => $data, 'drivers' => $drivers),  $info)->setPaper('legal','portrait');

        if($statusCount == true){
            $viewToEmail = "EmailApprovedwithChanges";
            $tempdata = $emaildata;
        }else{
            $viewToEmail = "EmailApproved";
        }

        $boxLabels = '';
        $itemCodes = '';
        $itemDescriptions = '';
        $brands = '';
        $quantities = '';

        foreach ($tempdata as $data) {
            $boxLabels .= $data->boxLabel . ',';
            $itemCodes .= $data->itemCode . ',';
            $itemDescriptions .= $data->itemDescription . ',';
            $brands .= $data->brand . ',';
            $quantities .= $data->quantity . ',';
        }

        // $sms = array(
        //     'recipient' => "+639",
        //     'message' => "We would like to inform you that your transaction no.".$request->plID." has been approved. The transaction has been processed and will proceed once the document was signed by the admin.",
        //     'sender' => "Barbizon Helpdesk"
        // );

        // $result = (new BrevoSMService($sms))->sendSMS();

        //SEND EMAIL IF APPROVED
        $mail = array(
            'transactionID' => $request->plID,
            'status' => 'Approved',
            'email' => $request->email,
            'name' => $request->name,
            'viewToEmail' => $viewToEmail,
            'adminName' => $request->adminName,
            'viewEmail' => $viewToEmail,
            'chainCode' => $tempdata[0]->chainCode,
            'branchName' => $tempdata[0]->branchName,
            'transactionType' => $tempdata[0]->transactionType,
            'boxLabels' => $boxLabels,
            'itemCodes'  => $itemCodes,
            'itemDescriptions' => $itemDescriptions,
            'brands' => $brands,
            'quantities' => $quantities,
            // 'result' => $result,
        );

        Mail::to($request->email)->send(new MailNotify($mail));

        return $pdf->stream($today.' '.$string.'.pdf');
    }

    public function sendDeniedBranch(Request $request){

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        if($request->company == "EPC"){
            $denied = DB::select('UPDATE pullOutBranchTbl
                            SET editedBy = \''.$request->name.'\',
                            updated_at = \''.$date.'\',
                            status = "denied"
                            WHERE id = \''.$request->id.'\' ');
        } else if($request->company == "NBFI"){
            $denied = DB::select('UPDATE pullOutBranchTblNBFI
                            SET editedBy = \''.$request->name.'\',
                            updated_at = \''.$date.'\',
                            status = "denied"
                            WHERE id = \''.$request->id.'\' ');
        }


        $data = array(
            'transactionID' => $request->id,
            'name' => $request->promoName,
            'status' => 'Denied',
            'reason' => $request->reason,
            'adminName' => $request->name
        );

        $res = Mail::to($request->email)->send(new MailNotify($data));

        return response()->json($denied);

    }

    public function savePullOutBranchRequest(Request $request){

        //CONDITION WITH THE COMPANY
        if ($request->chainCode == "RDS"){
            $company = 5; //AHLC
        }else{
            $company = 4; //EPC
        }

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        if($request->companyType == 'EPC')
            $branch = new PullOutBranchModel();
        else if($request->companyType == 'NBFI')
            $branch = new PullOutBranchModelNBFI();

        $branch->chainCode = $request->chainCode;
        $branch->company = $company;
        $branch->branchName = $request->branchName;
        $branch->transactionType = $request->transactionType;
        $branch->status = 'unprocessed';
        $branch->dateTime = $date;
        $branch->promoEmail = $request->email;
        //SAVING
        $branch->save();

        $dataID = DB::table('users')
                    ->select('id')
                    ->where('email', $request->email)
                    ->first();

        $log = new TransactionModel();
        $log->dateTime = $date;
        $log->userID = $dataID->id;
        $log->action_type = 'insert';
        $log->table_affected = 'pullOutBranchTbl';
        $log->new_data = json_encode($request->all());
        $log->save();

        return response()->json($branch);

    }

    public function savePullOutItemRequest(Request $request){

        //GETTING THE EFFECTIVE PRICE OF THE SPECIFIC ITEM
        if($request->companyType == 'EPC'){
            $item = new PullOutItemModel();
            $price = DB::table('epc_items')
                        ->select('EffectivePrice')
                        ->where('ItemNo', '=', $request->itemCode)
                        ->first();
        }
        else if($request->companyType == 'NBFI'){
            $item = new PullOutItemModelNBFI();
            $price = DB::table('nbfi_items')
                        ->select('EffectivePrice')
                        ->where('ItemNo', '=', $request->itemCode)
                        ->first();
        }

        //COMPUTATION TOTAL AMOUNT
        $amount = floatval($price->EffectivePrice) * floatval($request->quantity);

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        $item->plID = $request->plID;
        $item->brand = $request->brand;
        $item->boxNumber = $request->boxNumber;
        $item->boxLabel = $request->boxLabel;
        $item->itemCode = $request->itemCode;
        $item->quantity = $request->quantity;
        $item->amount = $amount;
        $item->status = 'unprocessed';
        $item->dateTime = $date;

        //SAVING
        $item->save();

        $dataID = DB::table('users')
                    ->select('id')
                    ->where('email', $request->email)
                    ->first();



        $log = new TransactionModel();
        $log->dateTime = $date;
        $log->userID = $dataID->id;
        $log->action_type = 'insert';
        $log->table_affected = 'pullOutItemsTbl';
        $log->new_data = json_encode($request->all());
        $log->save();

        return response()->json($item);

    }

    public function addNewBranch(Request $request){

        $input = new EpcBranchModel();
        $input->chainCode = $request->chainCode;
        $input->branchCode = strtoupper($request->branchCode);
        $input->branchName = strtoupper($request->branchName);
        $input->status = 'Active';

        $input->save();

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        $log = new TransactionModel();
        $log->dateTime = $date;
        $log->userID = $request->userID;
        $log->action_type = 'insert';
        $log->table_affected = 'epcbrandsmaintenance';
        $log->new_data = json_encode($request->all());
        $log->save();

        return response()->json($input);
    }

    public function addNewBrand(Request $request){

        $data = EpcBrandModel::orderBy('id', 'desc')->first()->id;

        $id = $data + 1;

        $input = new EpcBrandModel();
        $input->id = $id;
        $input->brandNames = strtoupper($request->brandName);
        $input->status = 'Y';

        $input->save();

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        $log = new TransactionModel();
        $log->dateTime = $date;
        $log->userID = $request->userID;
        $log->action_type = 'insert';
        $log->table_affected = 'epcbrandsmaintenance';
        $log->new_data = json_encode($request->all());
        $log->save();

        return response()->json($input);
    }

    public function addNewDriver(Request $request){

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        $input = new EpcDriverModel();
        $input->name = ucwords($request->name);
        $input->position = $request->position;
        $input->dateTime = $date;
        $input->status = 'Active';

        $input->save();

        $log = new TransactionModel();
        $log->dateTime = $date;
        $log->userID = $request->userID;
        $log->action_type = 'insert';
        $log->table_affected = 'drivermaintenance';
        $log->new_data = json_encode($request->all());
        $log->save();

        return response()->json($input);
    }

    public function addNewReason(Request $request){

        $date = now()->timezone('Asia/Manila'); // GETTING THE TIME ZONE IN PH

        $input = new EpcReasonModel();
        $input->reasonLabel = ucfirst($request->reasonLabel);
        $input->dateTime = $date;
        $input->status = 'Active';

        $input->save();

        $log = new TransactionModel();
        $log->dateTime = $date;
        $log->userID = $request->userID;
        $log->action_type = 'insert';
        $log->table_affected = 'reasonmaintenance';
        $log->new_data = json_encode($request->all());
        $log->save();

        return response()->json($input);
    }

    public function generatePDF1(Request $request){


        $info = [
            'name' => $request->name,
            'company' => $request->company,
            'date' => $request->date,

        ];

        if ($request->company == "RDS"){
            $letter = "AHLC";
        }else{
            $letter = "pullOutLetter";
        }
        $id = $request->input('plID');

        $data = DB::table('pullOutTbl')
                ->select('branchName',
                'brand', 'transactionType', DB::raw('CAST(dateTime AS DATE) as date'),DB::raw('TIME(dateTime) as time'),'boxLabel', 'itemCode', 'quantity', 'amount')
                ->where('plID', $id)
                ->get();

        $pdf = PDF::loadView('PullOutLetter', array('data' => $data),  $info)->setPaper('legal','portrait');
        return $pdf->stream('pullOut.pdf');
    }

    public function generateReport(Request $request){

        $name = $request->name;
        $company = $request->company;
        $date = $request->date;

        $title = 'Pull Out Letter';

        $meta = [
            'Name' => $name,
            'Company' => $company,
            'Date' => $date
        ];

        $queryBuilder = DB::table('pullOutTbl as a')
                        ->join('companyTbl as b', 'a.company', '=', 'b.id')
                        ->select(DB::raw(('(SELECT shortName FROM companyTbl WHERE id = a.company) as company')),'a.plID', 'a.chainCode', 'a.branchName', 'a.amount',
                        'a.brand', 'a.transactionType', DB::raw('CAST(a.dateTime AS DATE) as date'),
                        DB::raw('TIME(dateTime) as time'))
                        ->distinct()
                        ->get();

        $columns = [
            'Company' => 'company',
            'Chain Code' => 'chainCode',
            'Branch Name' => 'branchName',
            'Amount' => 'amount'

        ];

        return PdfReport::of($title, $meta, $queryBuilder, $columns)->stream();

    }
}
