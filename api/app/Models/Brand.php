<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    protected $fillable = ['business_id', 'name', 'image'];

    public function business() { return $this->belongsTo(Business::class); }
    public function products() { return $this->hasMany(Product::class); }
}
