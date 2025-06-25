<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeClearSafe extends Command
{
    protected $signature = 'optimize:clear-safe';
    
    protected $description = 'Clear all cached bootstrap files and regenerate autoloader safely';

    public function handle()
    {
        $this->info('Clearing cached bootstrap files safely...');
        
        // Clear individual caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        
        // Clear additional caches
        if ($this->laravel->runningInConsole()) {
            $this->call('event:clear');
        }
        
        // Regenerate optimized autoloader
        $this->info('Regenerating optimized autoloader...');
        exec('cd ' . base_path() . ' && composer dump-autoload --optimize 2>&1', $output, $return);
        
        if ($return === 0) {
            $this->info('Autoloader regenerated successfully!');
        } else {
            $this->error('Failed to regenerate autoloader: ' . implode("\n", $output));
            return 1;
        }
        
        $this->info('All caches cleared and autoloader regenerated safely!');
        return 0;
    }
}