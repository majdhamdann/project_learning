<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;

class ConversationController extends Controller
{
    
//انشاء محادثة 


public function createConversation(Request $request)
{
    $user = auth()->user();

    if ($user->role_id == 1) { 
      
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $teacherId = $request->receiver_id;
        $studentId = $user->id;

    } elseif ($user->role_id == 2) { 
      
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $teacherId = $user->id;
        $studentId = $request->receiver_id;

    } else {
        return response()->json(['message' => 'Invalid role'], 403);
    }

    
    $conversation = Conversation::where('teacher_id', $teacherId)
        ->where('student_id', $studentId)
        ->first();

    if (!$conversation) {
        $conversation = Conversation::create([
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
        ]);
    }

    return response()->json([
        'message' => 'Conversation created successfully',
        'conversation' => $conversation
    ]);
}

//عرض المحادثات 
public function myConversations(Request $request)
{
    $user = auth()->user();

    // جلب المحادثات اللي المستخدم طرف فيها
    $conversations = Conversation::where('student_id', $user->id)
        ->orWhere('teacher_id', $user->id)
        ->with(['student', 'teacher'])
        ->get();

    // تجهيز الاستجابة
    $result = $conversations->map(function ($conversation) use ($user) {
        // تحديد الطرف الآخر
        $otherUser = $conversation->student_id == $user->id
            ? $conversation->teacher
            : $conversation->student;

        // حساب عدد الرسائل غير المقروءة
        $unreadCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return [
            'conversation_id' => $conversation->id, 
            'user' => $otherUser,                   
            'unread_count' => $unreadCount         
        ];
    });

    return response()->json($result);
}



// ارسال رسالة 

public function sendMessage(Request $request, $conversationId)
{
    $user = auth()->user();

    // التحقق من وجود المحادثة
    $conversation = Conversation::findOrFail($conversationId);

    // التحقق أن المستخدم هو جزء من المحادثة
    if ($conversation->student_id !== $user->id && $conversation->teacher_id !== $user->id) {
        return response()->json(['message' => 'You are not a participant in this conversation.'], 403);
    }

    // التحقق من المدخلات
    $request->validate([
        'message' => 'required|string',
    ]);

    // إنشاء الرسالة
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $user->id,
        'message'         => $request->message,
        'is_read'         => false,
    ]);

    return response()->json([
        'message' => 'Message sent successfully.',
       // 'data'    => $message
    ], 201);
}

//عرض رسائل محادثة 

public function getConversationMessages($conversationId)
{
    $user = auth()->user();

   
    $conversation = Conversation::where('id', $conversationId)
        ->where(function ($query) use ($user) {
            $query->where('student_id', $user->id)
                  ->orWhere('teacher_id', $user->id);
        })
        ->first();

    if (!$conversation) {
        return response()->json(['message' => 'Conversation not found or unauthorized'], 404);
    }

    
    $messages = Message::where('conversation_id', $conversationId)
    ->where('is_read', true)
        ->orderBy('created_at', 'asc')
        ->get();

    
    $unreadMessages = Message::where('conversation_id', $conversationId)
        ->where('sender_id', '!=', $user->id)
        ->where('is_read', false)
        ->get();

   
    Message::where('conversation_id', $conversationId)
        ->where('sender_id', '!=', $user->id)
        ->where('is_read', false)
        ->update(['is_read' => true]);

    return response()->json([
        
        'user_id'  => $user->id,
        'messages'          => $messages,
        'unread_messages'   => $unreadMessages
    ]);
}


}
