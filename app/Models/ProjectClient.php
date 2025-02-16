<?php
// Client Management Models
// ProjectClient.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectClient extends Model
{
    use HasFactory;

    protected $table = 'project_clients';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'identification_number',
        'file',
        'create_by_id',
        'status'
    ];

    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectClientDocument::class, 'client_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
