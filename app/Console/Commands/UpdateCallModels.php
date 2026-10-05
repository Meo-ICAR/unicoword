<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateCallModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:call-models {--dry-run : Show changes without applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all CALL models to extend BaseModel and use correct namespace';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Updating CALL models...');
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

            // Update namespace
            $content = preg_replace(
                '/^namespace App\\\\Models;/m',
                'namespace App\Models\CALL;',
                $content
            );

            // Add BaseModel import if not present
            if (!str_contains($content, 'use App\Models\CALL\BaseModel;')) {
                $content = preg_replace(
                    '/^use Illuminate\\\\Database\\\\Eloquent\\\\Model;/m',
                    "use App\Models\CALL\BaseModel;\nuse Illuminate\Database\Eloquent\Model;",
                    $content
                );
            }

            // Update class to extend BaseModel
            $content = preg_replace(
                '/^class ' . $fileName . ' extends Model/m',
                'class ' . $fileName . ' extends BaseModel',
                $content
            );

            // Fix internal model references
            $content = $this->fixModelReferences($content, $fileName);

            if ($content !== $originalContent) {
                if ($dryRun) {
                    $this->line('   📋 Changes detected (dry run)');
                } else {
                    File::put($filePath, $content);
                    $this->line('   ✅ Updated');
                    $updatedCount++;
                }
            } else {
                $this->line('   ℹ️  No changes needed');
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

    private function fixModelReferences(string $content, string $currentModel): string
    {
        // Common CALL model references to fix
        $callModels = [
            'Address', 'AddressType', 'Branch', 'Client', 'ClientType',
            'CodeRegistration', 'Company', 'CompanyUser', 'DataBreach',
            'Dpia', 'DpiaImpact', 'DpiaItem', 'DpiaRisk', 'Employee',
            'PrivacyAsset', 'PrivacyDataType', 'PrivacyLegalBasis',
            'PrivacyRetention', 'PrivacySecurity', 'PrivacySubject',
            'PurchaseInvoice', 'Registration', 'RegistroTrattamenti',
            'RegistroTrattamentiItem', 'SalesInvoice', 'SocialiteUser',
            'SoftwareApplication', 'SoftwareCategory', 'Subappalti',
            'User', 'Website'
        ];

        foreach ($callModels as $model) {
            if ($model !== $currentModel) {
                // Fix references like Address::class
                $content = preg_replace(
                    '/(?<!App\\\\Models\\\\CALL\\\\)' . $model . '::class/',
                    'App\\Models\\CALL\\' . $model . '::class',
                    $content
                );

                // Fix use statements
                $content = preg_replace(
                    '/^use App\\\\Models\\\\' . $model . ';/m',
                    'use App\\Models\\CALL\\' . $model . ';',
                    $content
                );
            }
        }

        return $content;
    }
}
