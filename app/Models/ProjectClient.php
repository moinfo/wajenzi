<?php
// Client Management Models
// ProjectClient.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;

class ProjectClient extends Authenticatable implements ApprovableModel
{
    use HasFactory, HasApiTokens, Approvable {
        Approvable::submit as protected traitSubmit;
    }

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
        'client_source_id',
        'status',
        'document_number',
        'password',
        'portal_access_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (ProjectClient $client) {
            // Nullify nullable references that should not be deleted
            DB::table('leads')->where('client_id', $client->id)->update(['client_id' => null]);
            DB::table('project_schedules')->where('client_id', $client->id)->update(['client_id' => null]);

            // Delete sales records tied to this client
            DB::table('sales_lead_followups')->where('client_id', $client->id)->delete();
            DB::table('sales_client_concerns')->where('client_id', $client->id)->delete();

            // Delete billing payments first (references billing_documents), then the documents
            $billingDocIds = DB::table('billing_documents')->where('client_id', $client->id)->pluck('id');
            if ($billingDocIds->isNotEmpty()) {
                DB::table('billing_payments')->whereIn('document_id', $billingDocIds)->delete();
                DB::table('billing_document_items')->whereIn('document_id', $billingDocIds)->delete();
                DB::table('billing_documents')->where('client_id', $client->id)->delete();
            }

            // Delete each project individually so Project::deleting fires and cascades further
            $client->projects()->each(fn(Project $project) => $project->delete());

            // project_client_documents are handled by DB-level cascade
        });
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Logic executed when the approval process is completed.
     *
     * This method handles the state transitions based on your application's status values:
     * 'CREATED', 'PENDING', 'APPROVED', 'REJECTED', 'PAID', 'COMPLETED'
     *
     * @param ProcessApproval $approval The approval object
     * @return bool Whether the approval completion logic succeeded
     */
    /**
     * Allow any authenticated user to submit — not restricted to the creator.
     */
    public function canBeSubmittedBy(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return !$this->isSubmitted();
    }

    /**
     * Override to bypass the creator-only guard inside the trait's submit().
     * We reassign creator_id to the current user so the trait check passes.
     */
    public function submit(?\Illuminate\Contracts\Auth\Authenticatable $user = null): \RingleSoft\LaravelProcessApproval\Models\ProcessApproval|\Illuminate\Http\RedirectResponse|bool
    {
        if ($this->approvalStatus?->creator_id) {
            $this->approvalStatus()->update(['creator_id' => \Illuminate\Support\Facades\Auth::id()]);
            $this->unsetRelation('approvalStatus');
        }
        return $this->traitSubmit($user);
    }

    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {

        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();
        return true;
    }

    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }

    public function client_source(){
        return $this->belongsTo(ClientSource::class,'client_source_id');
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
