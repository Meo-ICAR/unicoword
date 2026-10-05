<?php

namespace App\Services;

use App\Models\DocumentTemplateVar;
use TomatoPHP\FilamentDocs\Services\Contracts\DocsVar;

class DocumentTemplateService
{
    /**
     * Get all document template variables for a specific template as DocsVar array
     */
    public static function getTemplateVars(int $templateId): array
    {
        $vars = DocumentTemplateVar::forTemplate($templateId)->get();

        return $vars->map(function ($var) {
            return DocsVar::make($var->var)
                ->label($var->var)
                ->model($var->model)
                ->column($var->value);
        })->toArray();
    }

    /**
     * Get document template variables with dynamic values for a specific record
     */
    public static function getTemplateVarsWithRecord(int $templateId, $record): array
    {
        $vars = DocumentTemplateVar::forTemplate($templateId)->get();

        return $vars->map(function ($var) use ($record) {
            $docsVar = DocsVar::make($var->var)
                ->label($var->var)
                ->model($var->model);

            // If the variable has a model and column, get the value from the record
            if ($var->model && $var->value && class_exists($var->model)) {
                $column = is_string($var->value) ? $var->value : json_decode($var->value, true)['column'] ?? null;
                if ($column && method_exists($record, 'getAttribute')) {
                    // Handle nested relationships like company.name
                    if (str_contains($column, '.')) {
                        $parts = explode('.', $column);
                        $value = $record;
                        foreach ($parts as $part) {
                            if ($value && method_exists($value, $part)) {
                                $value = $value->$part;
                            } elseif ($value && isset($value->$part)) {
                                $value = $value->$part;
                            } else {
                                $value = null;
                                break;
                            }
                        }
                        $docsVar->value($value);
                    } else {
                        $docsVar->value($record->getAttribute($column));
                    }
                }
            }

            return $docsVar;
        })->toArray();
    }

    /**
     * Get all template variables as a simple array for debugging or display
     */
    public static function getTemplateVarsArray(int $templateId): array
    {
        return DocumentTemplateVar::forTemplate($templateId)
            ->get()
            ->map(function ($var) {
                return [
                    'var' => $var->var,
                    'model' => $var->model,
                    'value' => $var->value,
                ];
            })
            ->toArray();
    }

    /**
     * Create or update template variables from an array
     */
    public static function syncTemplateVars(int $templateId, array $vars): void
    {
        // Delete existing vars for this template
        DocumentTemplateVar::forTemplate($templateId)->delete();

        // Create new vars
        foreach ($vars as $varData) {
            DocumentTemplateVar::create([
                'document_template_id' => $templateId,
                'var' => $varData['var'],
                'model' => $varData['model'] ?? null,
                'value' => $varData['value'] ?? null,
            ]);
        }
    }
}
