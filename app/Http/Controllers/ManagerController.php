<?php

namespace App\Http\Controllers;

use App\Models\ManagerLeaves;
use App\Models\UserLeaves;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Validator;


class ManagerController extends Controller
{
    
    public function AddManagerLeave(Request $request)
    {
        $request->validate([
            'leavecategory' => 'required|string',
            'leavetype' => 'required|string',
            'reason' => 'required|string',
            'noofdays' => 'required|integer',
            'issandwich' => 'required|string',  
            'fromdate'=>'required|string',
            'todate'=>'required|string',
            'user_id' => 'required|string',
        ]);

        $leave = new  ManagerLeaves;
        $leave->leavecategory = $request->leavecategory;
        $leave->leavetype = $request->leavetype;
        $leave->issandwich = $request->issandwich;
        $leave->fromdate = $request->fromdate;
        $leave->todate = $request->todate;
        $leave->noofdays = $request->noofdays;
        $leave->reason = $request->reason;
        $leave->user_id = $request->user_id;
        
        $data = $leave->save();
        $user = auth()->user();
        
        if ($data) {
            $email = Auth::user()->email;
            // $email1 = ['kartik@ycstech.in'];
            $messageData = [
                'username' => $user->name,
                'leavecategory' => $leave->leavecategory,
                'leavetype' => $leave->leavetype,
                'issandwich' => $leave->issandwich,
                'fromdate' => $leave->fromdate,
                'todate' => $leave->todate,
                'noofdays' => $leave->noofdays,
                
                'reason' => $leave->reason,
            ];
    
            Mail::send('emails.userLeave', $messageData, function ($message) use ($email, $leave) {
                $message->to($email)
                        ->subject('Leave Request');
                        
            });
            // Mail::send('emails.userLeave', $messageData, function ($message) use ($email1, $leave) {
            //     $message->to($email1)
            //             ->subject('Leave Request')
            //             ->cc(json_decode($leave->cc));
            // });
    
            return response()->json([
                'status' => 200,
                'message' => 'Data saved and email sent successfully',
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'error' => 'Something went wrong',
            ]);
        }
    }

    public function getManagerLeave()
    {    
        $leave = ManagerLeaves::orderBy('id', 'desc')->get();
        return response()->json($leave);
    }


    public function getManagerUpdateLeave($id)
    {
        $leave = ManagerLeaves::find($id);    
        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }   
        return response()->json($leave);
    }

// for sandwich leave
    public function getSandwichLeaves()
    {
        $userId = auth()->id(); 
        $leave = ManagerLeaves::orderBy('id', 'desc')->get();
        return response()->json(['leaves' => $leave]);
    }


    public function deleteManagerLeave(Request $request,$id){
        $leave = ManagerLeaves::find($id);
        if ($leave) {
            
            if ($leave->status == 'Approved') {
                return response()->json(['message' => 'Approved leave cannot be deleted.'], 403);
            } 
            $leave->delete();
            return response()->json(['message' => 'Leave deleted successfully.'], 200);
        } else {
            return response()->json(['message' => 'Leave not found.'], 404);
        }
      

    }

    
public function ManagerLeaveRequests(){
    $leaves = UserLeaves::orderBy('id','desc')->with('user:id,name')->get();
    return response()->json($leaves);
  }

  public function leaveRequestDetails( Request $request,$id)
  {
      $leave = UserLeaves::where('id', $id)->first();
      if ($leave) {
          return response()->json($leave, 200);
      } else {
          return response()->json(['message' => 'Leave not found'], 404);
      }
     
  }

//   public function updateStatus(Request $request, $id)
// {
//     $request->validate([
//         'status' => 'required|string|in:Approved,Cancelled',
//         'actionreason' => 'required|string',
//     ]);

//     $leave = UserLeaves::findOrFail($id);
//     $newStatus = $request->status;
//     $oldStatus = $leave->status;
//     $user = User::find($leave->user_id);

//     if (!$user) {
//         return response()->json(['error' => 'User not found'], 404);
//     }

//     if ($newStatus === 'Approved' && $oldStatus !== 'Approved'&& $leave->leavetype === 'Full Day') {
//         $currentMonth = Carbon::now()->format('Y-m');
//         $leaveMonth = Carbon::parse($leave->fromdate)->format('Y-m');

//         if ($currentMonth === $leaveMonth) {
//             $remainingPaidLeaves = $user->paidleaves;
//             $paidLeavesToDeduct = min($leave->noofdays, $remainingPaidLeaves);
//             $user->paidleaves -= $paidLeavesToDeduct;
//             $user->save();
//         }
//     }

//     $leave->status = $newStatus;
//     $leave->actionreason = $request->actionreason;
//     $leave->save();
//     $userEmail = $user->email;
//     $userName = $user->name;
//     $messageData = [
//         'username' => $userName,
//         'leavetype' => $leave->leavetype,
//         'leavecategory' => $leave->leavecategory,
//         'issandwich' => $leave->issandwich,
//         'fromdate' => $leave->fromdate,
//         'todate' => $leave->todate,
//         'noofdays' => $leave->noofdays,
//         'reason' => $leave->reason,
//         'actionreason' => $leave->actionreason,
//     ];
//     $subject = $newStatus === 'Approved' ? 'Leave Approved' : 'Leave Cancelled';
//     $emailTemplate = $newStatus === 'Approved' ? 'emails.approvedLeave' : 'emails.cancelledLeave';

 
//     Mail::send($emailTemplate, $messageData, function ($message) use ($userEmail, $subject) {
//         $message->to($userEmail)->subject($subject);
//     });

//     return response()->json(['message' => "Leave $newStatus successfully"]);
// }
    

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|string|in:Approved,Cancelled',
        'actionreason' => 'required|string',
    ]);

    $leave = UserLeaves::findOrFail($id);
    $newStatus = $request->status;
    $oldStatus = $leave->status;
    $user = User::find($leave->user_id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    if ($newStatus === 'Approved' && $oldStatus !== 'Approved' && $leave->leavetype === 'Full Day') {
        $currentMonth = Carbon::now()->format('Y-m');
        $leaveMonth = Carbon::parse($leave->fromdate)->format('Y-m');

        if ($currentMonth === $leaveMonth) {
            $remainingPaidLeaves = $user->paidleaves;
            $paidLeavesToDeduct = min($leave->noofdays, $remainingPaidLeaves);
            $user->paidleaves -= $paidLeavesToDeduct;
            $user->save();
        }
    }
   
    elseif ($newStatus === 'Cancelled' && $oldStatus === 'Approved' && $leave->leavetype === 'Full Day') {
        $currentMonth = Carbon::now()->format('Y-m');
        $leaveMonth = Carbon::parse($leave->fromdate)->format('Y-m');
   
        if ($currentMonth === $leaveMonth) {
            $leaveDays = $leave->noofdays;
            $paidLeavesToRestore = min($leaveDays, 2 - $user->paidleaves);
            $user->paidleaves = min($user->paidleaves + $paidLeavesToRestore, 2); 
            $user->save();
        }
    }
    $leave->status = $newStatus;
    $leave->actionreason = $request->actionreason;
    $leave->save();
 
    $messageData = [
        'username' => $user->name,
        'leavetype' => $leave->leavetype,
        'leavecategory' => $leave->leavecategory,
        'issandwich' => $leave->issandwich,
        'fromdate' => $leave->fromdate,
        'todate' => $leave->todate,
        'noofdays' => $leave->noofdays,
        'reason' => $leave->reason,
        'actionreason' => $leave->actionreason,
    ];
    $subject = $newStatus === 'Approved' ? 'Leave Approved' : 'Leave Cancelled';
    $emailTemplate = $newStatus === 'Approved' ? 'emails.approvedLeave' : 'emails.cancelledLeave';

    Mail::send($emailTemplate, $messageData, function ($message) use ($user, $subject) {
        $message->to($user->email)->subject($subject);
    });

   
    return response()->json(['message' => "Leave $newStatus successfully"]);
}



public function Managerattendance(Request $request)
{
    $users = User::whereNotIn('role', ['manager', 'admin'])->get();
    $data = [];
    foreach ($users as $user) {     
        $leaves = UserLeaves::where('user_id', $user->id)->get();
        $leavesData = $leaves->map(function ($leave) {
            return [
                'leavetype' => $leave->leavetype,
                'status' => $leave->status,
                'fromdate' => Carbon::parse($leave->fromdate)->format('Y-m-d'),
                'todate' => Carbon::parse($leave->todate)->format('Y-m-d'),
            ];
        });
        $data[] = [
            'employee_name' => $user->name,
            'leaves' => $leavesData->isEmpty() ? [] : $leavesData->toArray(),
        ];
    }

    return response()->json($data);
}
   
public function updateManagerLeave(Request $request)
{
    $validator = Validator::make($request->all(), [
        'fromdate' => 'required|date',
        'todate' => 'required|date',
        'leavecategory' => 'required|string',
        'leavetype' => 'required|string',
        'reason' => 'required|string',
        'noofdays' => 'required|integer',
        'issandwich' => 'required|string', 
        'user_id' => 'required|string'   
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $leave = ManagerLeaves::find($request->id);

    if (!$leave) {
        return response()->json(['error' => 'Leave record not found'], 404);
    }

    $leave->leavetype = $request->leavetype;
    $leave->leavecategory = $request->leavecategory;
    $leave->reason = $request->reason;
    $leave->fromdate = $request->fromdate;
    $leave->todate = $request->todate;
    $leave->noofdays = $request->noofdays;
    $leave->issandwich = $request->issandwich;
    $leave->save();

    $email = Auth::user()->email;
    $username = Auth::user()->name; 
    $messageData = [
        'leavecategory' => $leave->leavecategory,
        'leavetype' => $leave->leavetype,
        'issandwich' => $leave->issandwich,
        'fromdate'=>$leave->fromdate,
        'todate'=>$leave->todate,
        'noofdays' => $leave->noofdays,
        'reason' => $leave->reason,
        'username' => $username,
    ];

    Mail::send('emails.updateUserLeave', $messageData, function ($message) use ($email, $leave) {
        $message->to($email)
                ->subject('Leave Request');
              
    });

    return response()->json(['message' => 'Leave request updated successfully'], 200);
}
       

}
