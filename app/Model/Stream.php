<?php

namespace App\Model;

use App\Providers\AttachmentServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\User;

class Stream extends Model
{
    /**
     * Streaming is currently playing.
     *
     * This is 'live', not 'in-progress', because that is what the column accepts:
     * streams.status is enum('pending','live','ended'). With 'in-progress' the value
     * could never be written -- MySQL rejects it outright in strict mode -- so going
     * live failed, and every read that filtered on it (browse-live, the watch page,
     * the ending/expiring stream crons) matched nothing by construction.
     *
     * App\Models\Stream, an unused parallel copy of this model, already had it right.
     */
    public const IN_PROGRESS_STATUS = 'live';

    /**
     * Streaming ended.
     */
    public const ENDED_STATUS = 'ended';

    /**
     * Stream deleted.
     *
     * Added to the column's enum by migration; it was previously unwritable for the
     * same reason as above.
     */
    public const DELETED_STATUS = 'deleted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'status', 'name', 'slug', 'poster', 'pushr_id', 'hls_link', 'vod_link', 'rtmp_server', 'rtmp_key', 'price', 'requires_subscription', 'sent_expiring_reminder', 'is_public', 'settings', 'title', 'type', 'is_live', 'started_at', 'ended_at', 'sdp_offer', 'sdp_answer'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'ended_at' => 'datetime',
        'settings' => 'array',
        'is_live' => 'boolean',
        'started_at' => 'datetime',
        'sdp_offer' => 'array',
        'sdp_answer' => 'array'
    ];

    public function getPosterAttribute($value)
    {
        if($value){
            if(getSetting('storage.driver') == 's3'){
                return 'https://'.getSetting('storage.aws_bucket_name').'.s3.'.getSetting('storage.aws_region').'.amazonaws.com/'.$value;
            }
            elseif(getSetting('storage.driver') == 'wasabi' || getSetting('storage.driver') == 'do_spaces'){
                return Storage::url($value);
            }
            elseif(getSetting('storage.driver') == 'minio'){
                return rtrim(getSetting('storage.minio_endpoint'), '/').'/'.getSetting('storage.minio_bucket_name').'/'.$value;
            }
            elseif(getSetting('storage.driver') == 'pushr'){
                return rtrim(getSetting('storage.pushr_cdn_hostname'), '/').'/'.$value;
            }
            else{
                return Storage::disk('public')->url($value);
            }
        }else{
            return asset('/img/live-stream-cover.svg');
        }

    }

    /**
     * Relationships.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany('App\Model\StreamMessage');
    }

    public function streamPurchases()
    {
        return $this->hasMany('App\Model\Transaction', 'stream_id', 'id')->where('status', 'approved')->where('type', 'stream-access');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class)->latest();
    }
}
