<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DateFormatTrait;
use App\Traits\LogsActivity;

class Chat extends Model
{
    use HasFactory, DateFormatTrait,LogsActivity;
    protected $fillable = ['sender_id','receiver_id'];
    protected $appends = ['last_message', 'last_message_date','student_user_id','student_name','unread_count','user'];


    /**
     * Get all of the message for the Chat
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function message()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the receiver that owns the Chat
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the receiver that owns the Chat
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getLastMessageAttribute()
    {
        
        $message = Message::where('chat_id', $this->id)->latest()->first();
        if ($message) {
            return $message->message;
        }
        
        return '';
    }
    public function getStudentUserIdAttribute()
    {
        $message = Message::where('chat_id', $this->id)->latest()->first();
        return $message ? $message->student_user_id : null;
    }
    public function getStudentNameAttribute()
    {
        $message = Message::where('chat_id', $this->id)
            ->whereNotNull('student_user_id')
            ->latest()
            ->first();

        if (!$message || !$message->student_user_id) {
            return null;
        }
        $user = User::find($message->student_user_id);
        if (!$user) {
            return null;
        }

        return trim($user->first_name . ' ' . $user->last_name);
    }



    public function getUserAttribute()
    {
        $user = null;
        $role = request('role');
        if ($this->sender_id == Auth::user()->id) {
            $user = User::select('id', 'first_name', 'last_name', 'image')->with('roles')->where('id',$this->receiver_id)
            ->with('subjectTeachers.subject','class_teacher.class_section.class','class_teacher.class_section.section','class_teacher.class_section.medium');
            if ($role != 'Staff') {
                $user = $user->role($role);
            }
            $user = $user->first();
        } else {
            $user = User::select('id', 'first_name', 'last_name', 'image')->with('roles')->where('id',$this->sender_id)
            ->with('subjectTeachers.subject','class_teacher.class_section.class','class_teacher.class_section.section','class_teacher.class_section.medium');
            if ($role != 'Staff') {
                $user = $user->role($role);
            }
            $user = $user->first();
        }
        return $user;
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
    public function getLastMessageDateAttribute()
{
    $message = Message::where('chat_id', $this->id)
        ->latest()
        ->first();

    if (!$message) {
        return null;
    }

    // agar DateFormatTrait use kar rahe ho
    return $this->formatDateValue($message->created_at);

    // OR simple format chahiye to
    // return $message->created_at->format('d-m-Y h:i A');
}
public function getUnreadCountAttribute()
{
    $userId = Auth::id();

    // last message se student_user_id nikalo
    $lastMessage = Message::where('chat_id', $this->id)
        ->latest()
        ->first();

    $query = Message::where('chat_id', $this->id)
        ->whereNull('read_at')
        ->where('sender_id', '!=', $userId);

    // 🔑 agar student_user_id NULL NAHI hai tab filter lage
    if ($lastMessage && $lastMessage->student_user_id) {
        $query->where('student_user_id', $lastMessage->student_user_id);
    }

    return $query->count();
}


}
