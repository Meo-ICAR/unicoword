<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class DpiaRisk extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'extra_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes
    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    public function scopeByExtraValue($query, $extraValue)
    {
        return $query->where('extra_value', $extraValue);
    }

    public function scopeWithDescription($query)
    {
        return $query->whereNotNull('description');
    }

    public function scopeWithoutDescription($query)
    {
        return $query->whereNull('description');
    }

    public function scopeWithExtraValue($query)
    {
        return $query->whereNotNull('extra_value');
    }

    public function scopeWithoutExtraValue($query)
    {
        return $query->whereNull('extra_value');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('extra_value', 'LIKE', "category:{$category}%");
    }

    public function scopeByWeight($query, $weight)
    {
        return $query->where('extra_value', $weight);
    }

    public function scopeHighWeight($query, $threshold = 3)
    {
        return $query->where('extra_value', '>=', $threshold);
    }

    public function scopeMediumWeight($query)
    {
        return $query->where('extra_value', 2);
    }

    public function scopeLowWeight($query, $threshold = 1)
    {
        return $query->where('extra_value', '<=', $threshold);
    }

    // Accessors
    public function getWeightAttribute(): int
    {
        // Se extra_value contiene un peso numerico, estrailo
        if (is_numeric($this->extra_value)) {
            return (int) $this->extra_value;
        }

        // Se extra_value contiene un peso con prefisso
        if (preg_match('/weight:(\d+)/', $this->extra_value, $matches)) {
            return (int) $matches[1];
        }

        // Altrimenti calcola un peso base dal nome
        return match (true) {
            str_contains(strtolower($this->name), 'critic') => 4,
            str_contains(strtolower($this->name), 'alt') => 3,
            str_contains(strtolower($this->name), 'med') => 2,
            str_contains(strtolower($this->name), 'bass') => 1,
            default => 2,
        };
    }

    public function getCategoryAttribute(): ?string
    {
        // Estrai categoria da extra_value
        if (preg_match('/category:([a-zA-Z]+)/', $this->extra_value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getRiskLevelAttribute(): string
    {
        $weight = $this->weight;

        return match ($weight) {
            4 => 'Critico',
            3 => 'Alto',
            2 => 'Medio',
            1 => 'Basso',
            default => 'Sconosciuto',
        };
    }

    public function getRiskColorAttribute(): string
    {
        $weight = $this->weight;

        return match ($weight) {
            4 => 'danger',
            3 => 'warning',
            2 => 'info',
            1 => 'success',
            default => 'gray',
        };
    }

    public function getIsoCodeAttribute(): ?string
    {
        // Se extra_value è un codice ISO, restituiscilo
        if ($this->extra_value && preg_match('/^ISO[0-9]{5}$/', $this->extra_value)) {
            return $this->extra_value;
        }

        return null;
    }

    public function getHasIsoCodeAttribute(): bool
    {
        return !is_null($this->iso_code);
    }

    public function getProbabilityRangeAttribute(): string
    {
        return match ($this->weight) {
            4 => '3-5 (Alta-Molto Alta)',
            3 => '2-4 (Media-Alta)',
            2 => '1-3 (Bassa-Media)',
            1 => '1-2 (Molto Bassa-Bassa)',
            default => 'N/A',
        };
    }

    // Methods
    public function isCriticalRisk(): bool
    {
        return $this->weight >= 4;
    }

    public function isHighRisk(): bool
    {
        return $this->weight >= 3;
    }

    public function isMediumRisk(): bool
    {
        return $this->weight === 2;
    }

    public function isLowRisk(): bool
    {
        return $this->weight <= 1;
    }

    public function setWeight(int $weight): void
    {
        $this->extra_value = (string) $weight;
        $this->save();
    }

    public function setCategory(string $category): void
    {
        $this->extra_value = "category:{$category}";
        $this->save();
    }

    public function setWeightAndCategory(int $weight, string $category): void
    {
        $this->extra_value = "weight:{$weight},category:{$category}";
        $this->save();
    }

    public function setIsoCode(string $isoCode): void
    {
        $this->extra_value = $isoCode;
        $this->save();
    }

    public function getFormattedDescription(): string
    {
        return $this->description ?: 'Nessuna descrizione disponibile';
    }

    // Constants
    const TECHNICAL_RISKS = [
        [
            'name' => 'Attacco informatico',
            'description' => 'Accesso non autorizzato a sistemi informatici, malware, ransomware',
            'extra_value' => 'weight:4,category:technical',
        ],
        [
            'name' => 'Vulnerabilità software',
            'description' => 'Bachi di sicurezza, exploit, vulnerabilità zero-day',
            'extra_value' => 'weight:3,category:technical',
        ],
        [
            'name' => 'Guasto hardware',
            'description' => 'Malfunzionamento di server, dispositivi di rete, storage',
            'extra_value' => 'weight:2,category:technical',
        ],
        [
            'name' => 'Interruzione servizi cloud',
            'description' => 'Downtime di provider cloud, perdita connettività',
            'extra_value' => 'weight:3,category:technical',
        ],
        [
            'name' => 'Perdita dati tecnica',
            'description' => 'Corruzione database, cancellazione accidentale, backup falliti',
            'extra_value' => 'weight:4,category:technical',
        ],
    ];

    const OPERATIONAL_RISKS = [
        [
            'name' => 'Errore umano',
            'description' => 'Distrazione, negligenza, formazione inadeguata del personale',
            'extra_value' => 'weight:3,category:operational',
        ],
        [
            'name' => 'Procedure inadeguate',
            'description' => 'Processi non standardizzati, mancanza di controlli',
            'extra_value' => 'weight:2,category:operational',
        ],
        [
            'name' => 'Insufficiente formazione',
            'description' => 'Personale non adeguatamente formato su privacy e sicurezza',
            'extra_value' => 'weight:2,category:operational',
        ],
        [
            'name' => 'Mancanza di supervisione',
            'description' => 'Controlli manageriali insufficienti, audit mancanti',
            'extra_value' => 'weight:2,category:operational',
        ],
        [
            'name' => 'Comunicazione interna inefficiente',
            'description' => 'Mancanza di canali di comunicazione chiari e tempestivi',
            'extra_value' => 'weight:1,category:operational',
        ],
    ];

    const PHYSICAL_RISKS = [
        [
            'name' => 'Accesso fisico non autorizzato',
            'description' => 'Intrusione in uffici, data center, aree restritte',
            'extra_value' => 'weight:3,category:physical',
        ],
        [
            'name' => 'Incendio o disastro naturale',
            'description' => 'Incendi, alluvioni, terremoti che danneggiano infrastrutture',
            'extra_value' => 'weight:2,category:physical',
        ],
        [
            'name' => 'Furto di dispositivi',
            'description' => 'Ruberie di laptop, server, smartphone contenenti dati',
            'extra_value' => 'weight:3,category:physical',
        ],
        [
            'name' => 'Manomissione apparecchiature',
            'description' => 'Modifica non autorizzata di hardware o dispositivi',
            'extra_value' => 'weight:2,category:physical',
        ],
        [
            'name' => 'Surveillance fisica insufficiente',
            'description' => 'Mancanza di controlli di accesso, telecamere, allarmi',
            'extra_value' => 'weight:2,category:physical',
        ],
    ];

    const LEGAL_COMPLIANCE_RISKS = [
        [
            'name' => 'Violazione GDPR',
            'description' => 'Non conformità al Regolamento Europeo Privacy',
            'extra_value' => 'weight:4,category:legal',
        ],
        [
            'name' => 'Sanzioni normative',
            'description' => 'Multe e sanzioni per violazione di leggi sulla privacy',
            'extra_value' => 'weight:3,category:legal',
        ],
        [
            'name' => 'Contenzioso legale',
            'description' => 'Cause legali da parte di interessati o autorità',
            'extra_value' => 'weight:3,category:legal',
        ],
        [
            'name' => 'Mancanza di consenso valido',
            'description' => 'Consenso non adeguato o revocato dai trattamenti',
            'extra_value' => 'weight:3,category:legal',
        ],
        [
            'name' => 'Violazione diritti interessati',
            'description' => 'Mancata risposta a richieste di accesso, rettifica, cancellazione',
            'extra_value' => 'weight:3,category:legal',
        ],
    ];

    const STRATEGIC_RISKS = [
        [
            'name' => 'Danno reputazionale',
            'description' => 'Perdita di fiducia clienti e partners, danno immagine',
            'extra_value' => 'weight:4,category:strategic',
        ],
        [
            'name' => 'Perdita vantaggio competitivo',
            'description' => 'Rivelazione di segreti industriali o strategie aziendali',
            'extra_value' => 'weight:3,category:strategic',
        ],
        [
            'name' => 'Impatto su valore aziendale',
            'description' => "Riduzione del valore dell'azienda per incidenti privacy",
            'extra_value' => 'weight:3,category:strategic',
        ],
        [
            'name' => 'Crisi di fiducia stakeholders',
            'description' => 'Perdita di fiducia da investitori, clienti, dipendenti',
            'extra_value' => 'weight:3,category:strategic',
        ],
    ];

    const ISO_27005_RISKS = [
        [
            'name' => 'Business disruption risk',
            'description' => 'Rischio di interruzione dei processi business critici',
            'extra_value' => 'ISO27005-001',
        ],
        [
            'name' => 'Information security breach',
            'description' => 'Violazione della sicurezza delle informazioni',
            'extra_value' => 'ISO27005-002',
        ],
        [
            'name' => 'Compliance violation risk',
            'description' => 'Rischio di violazione di compliance normative',
            'extra_value' => 'ISO27005-003',
        ],
        [
            'name' => 'Financial loss risk',
            'description' => 'Rischio di perdite finanziarie dirette o indirette',
            'extra_value' => 'ISO27005-004',
        ],
        [
            'name' => 'Reputational damage risk',
            'description' => 'Rischio di danno alla reputazione aziendale',
            'extra_value' => 'ISO27005-005',
        ],
    ];

    public static function getTechnicalRisks(): array
    {
        return self::TECHNICAL_RISKS;
    }

    public static function getOperationalRisks(): array
    {
        return self::OPERATIONAL_RISKS;
    }

    public static function getPhysicalRisks(): array
    {
        return self::PHYSICAL_RISKS;
    }

    public static function getLegalComplianceRisks(): array
    {
        return self::LEGAL_COMPLIANCE_RISKS;
    }

    public static function getStrategicRisks(): array
    {
        return self::STRATEGIC_RISKS;
    }

    public static function getIsoRisks(): array
    {
        return self::ISO_27005_RISKS;
    }

    public static function getAllRisks(): array
    {
        return array_merge(
            self::TECHNICAL_RISKS,
            self::OPERATIONAL_RISKS,
            self::PHYSICAL_RISKS,
            self::LEGAL_COMPLIANCE_RISKS,
            self::STRATEGIC_RISKS,
            self::ISO_27005_RISKS
        );
    }

    public static function getRiskOptions(): array
    {
        return collect(self::getAllRisks())
            ->pluck('name', 'name')
            ->toArray();
    }

    public static function findByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('extra_value', 'LIKE', "category:{$category}%")->get();
    }

    public static function findByWeight(int $weight): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('extra_value', 'LIKE', "%weight:{$weight}%")
            ->orWhere('extra_value', $weight)
            ->get();
    }

    public static function findByIsoCode(string $isoCode): ?self
    {
        return static::where('extra_value', $isoCode)->first();
    }

    public static function getCategories(): array
    {
        return [
            'technical' => 'Tecnico',
            'operational' => 'Operativo',
            'physical' => 'Fisico',
            'legal' => 'Legale/Compliance',
            'strategic' => 'Strategico',
        ];
    }

    public static function getCategoryOptions(): array
    {
        return self::getCategories();
    }
}
