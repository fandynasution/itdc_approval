<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\CmContractDone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CmContractCloseController extends Controller
{
    public function Mail(Request $request) {
        $new_doc_no = str_replace("/","-",$request->doc_no);
        $new_ref_no = str_replace("/","-",$request->ref_no);

        $dataArray = array(
            'entity_cd'         => $request->entity_cd,
            'project_no'        => $request->project_no,
            'doc_no'            => $new_doc_no,
            'ref_no'            => $new_ref_no,
            'contract_no'       => $request->contract_no,
            'contract_amt'      => $request->contract_amt,
            'ponumberoracle'    => $request->ponumberoracle,
            'requisitionnumber' => $request->requisitionnumber,
            'auth_vo'           => $request->auth_vo,
            'level_no'          => $request->level_no,
            'user_id'           => $request->user_id,
            'email_addr'        => $request->email_addr,
            'descs'             => $request->descs,
            'usergroup'         => $request->usergroup,
            'user_name'         => $request->user_name,
            'supervisor'        => $request->supervisor,
            'link'              => 'cmcontractclose',
            'body'              => 'Please Approve Contract Close with : ',
        );

        try {
            $emailAddresses = $request->email_addr;
            // Check if email addresses are provided and not empty
            if(isset($emailAddresses) && !empty($emailAddresses) && filter_var($emailAddresses, FILTER_VALIDATE_EMAIL)) {
                $emails = is_array($emailAddresses) ? $emailAddresses : [$emailAddresses];
                foreach ($emails as $email) {
                    Mail::to($email)->send(new CmContractDone($dataArray));
                }
                
                $sentTo = is_array($emailAddresses) ? implode(', ', $emailAddresses) : $emailAddresses;
                Log::channel('sendmail')->info('Email berhasil dikirim ke: ' . $sentTo);
                return "Email berhasil dikirim ke: " . $sentTo;
            } else {
                Log::channel('sendmail')->warning('Tidak ada alamat email yang diberikan.');
                return "Tidak ada alamat email yang diberikan.";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email: " . $e->getMessage();
        }
    }

    public function changestatus($entity_cd='', $project_no='', $doc_no='', $ref_no='', $status='', $level_no='', $usergroup='', $user_id='', $supervisor='')
    {

        $new_doc_no = str_replace("-","/",$doc_no);
        $new_ref_no = str_replace("-","/",$ref_no);

        $where2 = array(
            'entity_cd'     =>  $entity_cd,
            'doc_no'        =>  $new_doc_no,
            'ref_no'        =>  $new_ref_no,
            'status'        =>  array("A",'R', 'C'),
            'level_no'      =>  $level_no,
            'user_id'       =>  $user_id,
            'type'          =>  'D',
            'module'        =>  'CM'
        );

        $where3 = array(
            'entity_cd'     =>  $entity_cd,
            'doc_no'        =>  $new_doc_no,
            'ref_no'        =>  $new_ref_no,
            'status'        =>  'P',
            'level_no'      =>  $level_no,
            'user_id'       =>  $user_id,
            'type'          =>  'D',
            'module'        =>  'CM'
        );
        
        $query = DB::connection('ITDC')
        ->table('mgr.cb_cash_request_appr')
        ->where($where2)
        ->get();

        $query3 = DB::connection('ITDC')
        ->table('mgr.cb_cash_request_appr')
        ->where($where3)
        ->get();

        if(count($query)>0){
            $msg = 'You Have Already Made a Request to CM Contract Close No. '.$doc_no ;
            $notif = 'Restricted !';
            $st  = 'OK';
            $image = "double_approve.png";
            $msg1 = array(
                "Pesan" => $msg,
                "St" => $st,
                "notif" => $notif,
                "image" => $image
            );
        } else {
            if($status == 'A') {
                $pdo = DB::connection('ITDC')->getPdo();
                $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_cm_contract_close ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                $sth->bindParam(1, $entity_cd);
                $sth->bindParam(2, $project_no);
                $sth->bindParam(3, $doc_no);
                $sth->bindParam(4, $ref_no);
                $sth->bindParam(5, $status);
                $sth->bindParam(6, $level_no);
                $sth->bindParam(7, $usergroup);
                $sth->bindParam(8, $user_id);
                $sth->bindParam(9, $supervisor);
                $sth->execute();
                if ($sth == true) {
                    $msg = "You Have Successfully Approved the CM Contract Close No. ".$doc_no;
                    $notif = 'Approved !';
                    $st = 'OK';
                    $image = "approved.png";
                } else {
                    $msg = "You Failed to Approve the CM Contract Close No ".$doc_no;
                    $notif = 'Fail to Approve !';
                    $st = 'OK';
                    $image = "reject.png";
                }
            } else if($status == 'R'){
                $pdo = DB::connection('ITDC')->getPdo();
                $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_cm_contract_close ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                $sth->bindParam(1, $entity_cd);
                $sth->bindParam(2, $project_no);
                $sth->bindParam(3, $doc_no);
                $sth->bindParam(4, $ref_no);
                $sth->bindParam(5, $status);
                $sth->bindParam(6, $level_no);
                $sth->bindParam(7, $usergroup);
                $sth->bindParam(8, $user_id);
                $sth->bindParam(9, $supervisor);
                $sth->execute();
                if ($sth == true) {
                    $msg = "You Have Successfully Made a Revise Request on CM Contract Close No. ".$doc_no;
                    $notif = 'Revised !';
                    $st = 'OK';
                    $image = "revise.png";
                } else {
                    $msg = "You Failed to Make a Revise Request on CM Contract Close No. ".$doc_no;
                    $notif = 'Fail to Revised !';
                    $st = 'OK';
                    $image = "reject.png";
                }
            } else {
                $pdo = DB::connection('ITDC')->getPdo();
                $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_cm_contract_close ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                $sth->bindParam(1, $entity_cd);
                $sth->bindParam(2, $project_no);
                $sth->bindParam(3, $doc_no);
                $sth->bindParam(4, $ref_no);
                $sth->bindParam(5, $status);
                $sth->bindParam(6, $level_no);
                $sth->bindParam(7, $usergroup);
                $sth->bindParam(8, $user_id);
                $sth->bindParam(9, $supervisor);
                $sth->execute();
                if ($sth == true) {
                    $msg = "You Have Successfully Cancelled the CM Contract Close No. ".$doc_no;
                    $notif = 'Cancelled !';
                    $st = 'OK';
                    $image = "reject.png";
                } else {
                    $msg = "You Failed to Cancel the CM Contract Close No. ".$doc_no;
                    $notif = 'Fail to Cancelled !';
                    $st = 'OK';
                    $image = "reject.png";
                }
            }
            $msg1 = array(
                "Pesan" => $msg,
                "St" => $st,
                "image" => $image,
                "notif" => $notif
            );
        }
        return view("emails.after", $msg1);
    }
}