<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TomatoPHP\FilamentDocs\Facades\FilamentDocs;
use TomatoPHP\FilamentDocs\Models\Document;
use TomatoPHP\FilamentDocs\Models\DocumentTemplate;
use TomatoPHP\FilamentDocs\Services\Contracts\DocsVar;

class DocumentExportService
{
    /**
     * Generate document and export to PDF
     *
     * @param string $modelClass - Full model class name (e.g., App\Models\Post)
     * @param int $modelId - The ID of the record
     * @param int $templateId - The document template ID
     * @param array $additionalVars - Additional variables to include
     * @return array - Contains document info and PDF path
     */
    public static function generateAndExportPdf(
        string $modelClass,
        int $modelId,
        int $templateId,
        array $additionalVars = []
    ): array {
        // Validate model class
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        // Get the record
        $record = $modelClass::findOrFail($modelId);

        // Get template variables with record values
        $vars = DocumentTemplateService::getTemplateVarsWithRecord($templateId, $record);

        // Add any additional variables
        if (!empty($additionalVars)) {
            foreach ($additionalVars as $key => $value) {
                $vars[] = DocsVar::make($key)->value($value);
            }
        }

        // Get template from database
        $template = DocumentTemplate::findOrFail($templateId);

        // Replace variables in template body
        $body = $template->body;

        // Replace each variable with its value
        foreach ($vars as $var) {
            $varArray = $var->toArray();
            $varKey = $varArray['key'];
            $varValue = $varArray['value'];
            if ($varValue !== null) {
                $body = str_replace($varKey, $varValue, $body);
            }
        }

        // Create document record
        $document = Document::query()->create([
            'model_id' => $modelId,
            'model_type' => $modelClass,
            'document_template_id' => $templateId,
            'body' => $body,
        ]);

        // Generate PDF
        $pdfPath = self::generatePdf($document, $record);

        return [
            'document' => $document,
            'pdf_path' => $pdfPath,
            'pdf_url' => Storage::url($pdfPath),
            'record' => $record,
            'template_id' => $templateId,
        ];
    }

    /**
     * Generate PDF from document
     *
     * @param Document $document
     * @param mixed $record
     * @return string - Storage path to PDF
     */
    private static function generatePdf(Document $document, $record): string
    {
        // Create PDF using DomPDF
        $pdf = Pdf::loadView('pdf.document', [
            'document' => $document,
            'record' => $record,
            'body' => $document->body,
        ]);

        // Generate filename
        $modelName = class_basename($document->model_type);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "document_{$modelName}_{$document->model_id}_{$timestamp}.pdf";

        // Store PDF
        $path = "documents/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate PDF for existing document
     *
     * @param int $documentId
     * @return string - Storage path to PDF
     */
    public static function exportExistingDocument(int $documentId): string
    {
        $document = Document::findOrFail($documentId);
        $record = $document->model_type::findOrFail($document->model_id);

        return self::generatePdf($document, $record);
    }

    /**
     * Generate multiple documents and export as ZIP
     *
     * @param array $items - Array of ['model_class', 'model_id', 'template_id']
     * @return string - Storage path to ZIP file
     */
    public static function generateAndExportMultiple(array $items): string
    {
        $pdfPaths = [];
        $timestamp = now()->format('Y-m-d_H-i-s');

        // Generate individual PDFs
        foreach ($items as $item) {
            $result = self::generateAndExportPdf(
                $item['model_class'],
                $item['model_id'],
                $item['template_id'],
                $item['additional_vars'] ?? []
            );
            $pdfPaths[] = $result['pdf_path'];
        }

        // Create ZIP file
        $zipFilename = "documents_batch_{$timestamp}.zip";
        $zipPath = "documents/{$zipFilename}";

        $zip = new \ZipArchive();
        $zipPathFull = Storage::disk('public')->path($zipPath);

        if ($zip->open($zipPathFull, \ZipArchive::CREATE) === TRUE) {
            foreach ($pdfPaths as $pdfPath) {
                $fullPath = Storage::disk('public')->path($pdfPath);
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, basename($pdfPath));
                }
            }
            $zip->close();
        }

        return $zipPath;
    }

    /**
     * Download PDF directly
     *
     * @param string $modelClass
     * @param int $modelId
     * @param int $templateId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function downloadPdf(
        string $modelClass,
        int $modelId,
        int $templateId
    ): StreamedResponse {
        $result = self::generateAndExportPdf($modelClass, $modelId, $templateId);

        return Storage::disk('public')->download($result['pdf_path']);
    }

    /**
     * Get document preview without saving to database
     *
     * @param string $modelClass
     * @param int $modelId
     * @param int $templateId
     * @return string - HTML preview
     */
    public static function previewDocument(
        string $modelClass,
        int $modelId,
        int $templateId
    ): string {
        $record = $modelClass::findOrFail($modelId);
        $vars = DocumentTemplateService::getTemplateVarsWithRecord($templateId, $record);

        // Get template from database
        $template = DocumentTemplate::findOrFail($templateId);

        // Replace variables in template body
        $body = $template->body;

        // Replace each variable with its value
        foreach ($vars as $var) {
            $varArray = $var->toArray();
            $varKey = $varArray['key'];
            $varValue = $varArray['value'];
            if ($varValue !== null) {
                $body = str_replace($varKey, $varValue, $body);
            }
        }

        return $body;
    }
}
