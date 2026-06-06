<?php
// Construction Management Models
// ProjectBoq.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
use App\Models\Concerns\CascadesApprovalRecords;
use App\Models\User;

class ProjectBoq extends Model implements ApprovableModel
{
    use HasFactory, Approvable, CascadesApprovalRecords;

    protected $table = 'project_boqs';

    protected $fillable = [
        'project_id',
        'version',
        'type',
        'total_amount',
        'status'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2'
    ];

    // Alias for shared approval header partial
    public function getDocumentNumberAttribute(): ?string
    {
        $projectName = $this->project->project_name ?? 'BOQ';
        return $projectName . ' v' . $this->version;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectBoqItem::class, 'boq_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ProjectBoqSection::class, 'boq_id')->orderBy('sort_order');
    }

    public function rootSections(): HasMany
    {
        return $this->hasMany(ProjectBoqSection::class, 'boq_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function unsectionedItems(): HasMany
    {
        return $this->hasMany(ProjectBoqItem::class, 'boq_id')
            ->whereNull('section_id')
            ->orderBy('sort_order');
    }

    /**
     * Load hierarchical data for display/export.
     */
    public function getHierarchicalData(): self
    {
        return $this->load([
            'rootSections.childrenRecursive.items',
            'rootSections.items',
            'unsectionedItems',
        ]);
    }

    /**
     * Recalculate total from all items.
     */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_amount' => $this->items()->sum('total_price'),
        ]);
    }

    // Approvable interface implementation
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'approved';
        $this->save();

        // Update project to reflect BOQ approval
        $this->project?->update(['status' => 'boq_approved']);

        $link    = "/project_boq/show/{$this->id}";
        $message = "The BOQ for {$this->document_number} has been approved and is now available for client sharing.";

        // Notify Sales Team to share with client
        $salesUsers = User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['Sales and Marketing', 'Business Development Manager'])
        )->get();
        foreach ($salesUsers as $sales) {
            $sales->notify(new \App\Notifications\SystemActionNotification(
                'Final BOQ Approved — Ready to Share',
                $message,
                $link, null, $this->id
            ));
        }

        return true;
    }
}
