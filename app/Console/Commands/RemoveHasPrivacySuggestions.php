<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveHasPrivacySuggestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:has-privacy-suggestions {--dry-run : Show changes without applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove HasPrivacySuggestions trait from all CALL models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Removing HasPrivacySuggestions trait from CALL models...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $modelsPath = app_path('Models/CALL');

        if (!is_dir($modelsPath)) {
            $this->error("❌ CALL models directory not found: {$modelsPath}");
            return 1;
        }

        $modelFiles = glob($modelsPath . '/*.php');
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($modelFiles as $filePath) {
            $fileName = basename($filePath, '.php');

            // Skip BaseModel
            if ($fileName === 'BaseModel') {
                $this->line('⏭️  Skipping BaseModel');
                $skippedCount++;
                continue;
            }

            $this->info("📝 Processing: {$fileName}");

            $content = File::get($filePath);
            $originalContent = $content;

            // Remove use statement for HasPrivacySuggestions
            $content = preg_replace(
                '/^use App\\\\Traits\\\\HasPrivacySuggestions;\s*$/m',
                '',
                $content
            );

            // Remove HasPrivacySuggestions from trait usage
            $content = preg_replace(
                '/(use\s+[^;]*),\s*HasPrivacySuggestions([^;]*;)/',
                '$1$2',
                $content
            );

            // Remove HasPrivacySuggestions if it's the only trait
            $content = preg_replace(
                '/use\s+HasPrivacySuggestions\s*;\s*$/m',
                '',
                $content
            );

            // Clean up empty lines
            $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);

            if ($content !== $originalContent) {
                if ($dryRun) {
                    $this->line('   📋 Changes detected (dry run)');
                } else {
                    File::put($filePath, $content);
                    $this->line('   ✅ Updated');
                    $updatedCount++;
                }
            } else {
                $this->line('   ℹ️  No HasPrivacySuggestions found');
                $skippedCount++;
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("   Updated: {$updatedCount} models");
        $this->line("   Skipped: {$skippedCount} models");

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 This was a dry run. Run without --dry-run to apply changes.');
        }

        return 0;
    }
}
