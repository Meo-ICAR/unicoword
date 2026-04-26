<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\DocumentExportService;
use App\Services\DocumentTemplateService;
use Illuminate\Console\Command;

class TestDocumentExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:document-export {--template-id=1 : Template ID to use} {--post-id=1 : Post ID to use} {--client-id= : Client ID to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test DocumentExportService functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing DocumentExportService...');
        $this->newLine();

        $templateId = $this->option('template-id');
        $postId = $this->option('post-id');
        $clientId = $this->option('client-id');

        // Test with client if client-id is provided
        if ($clientId) {
            return $this->testWithClient($templateId, $clientId);
        }

        // Check if post exists
        $this->info("📋 Checking Post #{$postId}...");
        $post = Post::find($postId);

        if (!$post) {
            $this->error("❌ Post #{$postId} not found!");

            // Create a test post
            $this->info('📝 Creating test post...');
            $post = Post::create([
                'title' => 'Test Document Export',
                'type' => 'test',
                'content' => "Questo è un contenuto di test per l'esportazione del documento in PDF.",
            ]);
            $this->info("✅ Test post created with ID: {$post->id}");
            $postId = $post->id;
        } else {
            $this->info("✅ Post found: {$post->title}");
        }

        $this->newLine();

        // Test template variables
        $this->info("🔍 Checking template variables for template #{$templateId}...");
        $vars = DocumentTemplateService::getTemplateVarsArray($templateId);

        if (empty($vars)) {
            $this->warn("⚠️  No template variables found for template #{$templateId}");
        } else {
            $this->info('✅ Found ' . count($vars) . ' template variables:');
            foreach ($vars as $var) {
                $this->line("   - {$var['var']} (Model: {$var['model']}, Value: {$var['value']})");
            }
        }

        $this->newLine();

        // Test document preview
        $this->info('👁️  Testing document preview...');
        try {
            $preview = DocumentExportService::previewDocument(
                Post::class,
                $postId,
                $templateId
            );
            $this->info('✅ Preview generated successfully');
            $this->line('   Preview length: ' . strlen($preview) . ' characters');
        } catch (\Exception $e) {
            $this->error('❌ Preview failed: ' . $e->getMessage());
        }

        $this->newLine();

        // Test PDF generation
        $this->info('📄 Testing PDF generation...');
        try {
            $result = DocumentExportService::generateAndExportPdf(
                Post::class,
                $postId,
                $templateId,
                [
                    'GENERATED_AT' => now()->format('d/m/Y H:i:s'),
                    'TEST_MODE' => true
                ]
            );

            $this->info('✅ PDF generated successfully!');
            $this->newLine();

            // Display results
            $this->info('📊 Results:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Document ID', $result['document']->id],
                    ['Model Type', $result['document']->model_type],
                    ['Model ID', $result['document']->model_id],
                    ['Template ID', $result['document']->document_template_id],
                    ['PDF Path', $result['pdf_path']],
                    ['PDF URL', $result['pdf_url']],
                    ['Created At', $result['document']->created_at->format('d/m/Y H:i:s')],
                ]
            );

            $this->newLine();
            $this->info('🎉 SUCCESS! PDF saved at: ' . storage_path('app/public/' . $result['pdf_path']));

            // Check if file exists
            $fullPath = storage_path('app/public/' . $result['pdf_path']);
            if (file_exists($fullPath)) {
                $fileSize = filesize($fullPath);
                $this->info('📁 File size: ' . number_format($fileSize / 1024, 2) . ' KB');
            }

            return $result['pdf_path'];
        } catch (\Exception $e) {
            $this->error('❌ PDF generation failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Test document export with Client model
     */
    private function testWithClient(int $templateId, int $clientId): ?string
    {
        $this->info('🏢 Testing with Client model...');
        $this->newLine();

        // Check if client exists
        $this->info("📋 Checking Client #{$clientId}...");
        $client = \App\Models\CALL\Client::find($clientId);

        if (!$client) {
            $this->error("❌ Client #{$clientId} not found!");

            // Create a test client
            $this->info('📝 Creating test client...');
            $client = \App\Models\CALL\Client::create([
                'name' => 'Test Client Document Export',
                'email' => 'test@example.com',
                'phone' => '+39 123 456789',
                'is_person' => true,
            ]);
            $this->info("✅ Test client created with ID: {$client->id}");
            $clientId = $client->id;
        } else {
            $this->info("✅ Client found: {$client->name}");
        }

        $this->newLine();

        // Test template variables
        $this->info("🔍 Checking template variables for template #{$templateId}...");
        $vars = DocumentTemplateService::getTemplateVarsArray($templateId);

        if (empty($vars)) {
            $this->warn("⚠️  No template variables found for template #{$templateId}");
        } else {
            $this->info('✅ Found ' . count($vars) . ' template variables:');
            foreach ($vars as $var) {
                $this->line("   - {$var['var']} (Model: {$var['model']}, Value: {$var['value']})");
            }
        }

        $this->newLine();

        // Test document preview
        $this->info('👁️  Testing document preview...');
        try {
            $preview = DocumentExportService::previewDocument(
                \App\Models\CALL\Client::class,
                $clientId,
                $templateId
            );
            $this->info('✅ Preview generated successfully');
            $this->line('   Preview length: ' . strlen($preview) . ' characters');
        } catch (\Exception $e) {
            $this->error('❌ Preview failed: ' . $e->getMessage());
        }

        $this->newLine();

        // Test PDF generation
        $this->info('📄 Testing PDF generation...');
        try {
            $result = DocumentExportService::generateAndExportPdf(
                \App\Models\CALL\Client::class,
                $clientId,
                $templateId,
                [
                    'GENERATED_AT' => now()->format('d/m/Y H:i:s'),
                    'TEST_MODE' => true,
                    'DATABASE' => 'call'
                ]
            );

            $this->info('✅ PDF generated successfully!');
            $this->newLine();

            // Display results
            $this->info('📊 Results:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Document ID', $result['document']->id],
                    ['Model Type', $result['document']->model_type],
                    ['Model ID', $result['document']->model_id],
                    ['Template ID', $result['document']->document_template_id],
                    ['PDF Path', $result['pdf_path']],
                    ['PDF URL', $result['pdf_url']],
                    ['Created At', $result['document']->created_at->format('d/m/Y H:i:s')],
                ]
            );

            $this->newLine();
            $this->info('🎉 SUCCESS! PDF saved at: ' . storage_path('app/public/' . $result['pdf_path']));

            // Check if file exists
            $fullPath = storage_path('app/public/' . $result['pdf_path']);
            if (file_exists($fullPath)) {
                $fileSize = filesize($fullPath);
                $this->info('📁 File size: ' . number_format($fileSize / 1024, 2) . ' KB');
            }

            return $result['pdf_path'];
        } catch (\Exception $e) {
            $this->error('❌ PDF generation failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
}
