<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ImportRow extends Model
{
    use HasFactory;
    protected $fillable = ['import_batch_id', 'import_sheet_snapshot_id', 'row_number', 'status', 'raw_payload', 'mapped_payload', 'errors'];
    protected $casts = [
        'metadata'=>'array','details'=>'array','raw_payload'=>'array','mapped_payload'=>'array','errors'=>'array',
        'title_blocks'=>'array','heading_map'=>'array','response_body'=>'array',
        'is_active'=>'boolean','approved_at'=>'datetime','authorized_at'=>'datetime','paid_at'=>'datetime',
        'activated_at'=>'datetime','terminated_at'=>'datetime','closed_at'=>'datetime','locked_at'=>'datetime',
        'completed_at'=>'datetime','resolved_at'=>'datetime','locked_at'=>'datetime','occurred_on'=>'date'
    ];
    public function batch(){return $this->belongsTo(ImportBatch::class,'import_batch_id');} public function sheet(){return $this->belongsTo(ImportSheetSnapshot::class,'import_sheet_snapshot_id');}
}
