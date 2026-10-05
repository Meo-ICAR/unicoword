<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class DpiaImpact extends BaseModel
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

    // Accessors
    public function getWeightAttribute(): int
    {
        // Se extra_value contiene un peso numerico, estrailo
        if (is_numeric($this->extra_value)) {
            return (int) $this->extra_value;
        }

        // Altrimenti calcola un peso base dal nome
        return match (true) {
            str_contains(strtolower($this->name), 'catastrof') => 5,
            str_contains(strtolower($this->name), 'critic') => 4,
            str_contains(strtolower($this->name), 'signific') => 3,
            str_contains(strtolower($this->name), 'moder') => 2,
            str_contains(strtolower($this->name), 'min') => 1,
            default => 1,
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        $weight = $this->weight;

        return match ($weight) {
            5 => 'Catastrofico',
            4 => 'Critico',
            3 => 'Significativo',
            2 => 'Moderato',
            1 => 'Minore',
            default => 'Sconosciuto',
        };
    }

    public function getSeverityColorAttribute(): string
    {
        $weight = $this->weight;

        return match ($weight) {
            5 => 'danger',
            4 => 'warning',
            3 => 'info',
            2 => 'secondary',
            1 => 'success',
            default => 'gray',
        };
    }

    public function getIsoCodeAttribute(): ?string
    {
        // Se extra_value è un codice ISO, restituiscilo
        if ($this->extra_value && preg_match('/^[A-Z]{2}[0-9]{3}$/', $this->extra_value)) {
            return $this->extra_value;
        }

        return null;
    }

    public function getHasIsoCodeAttribute(): bool
    {
        return !is_null($this->iso_code);
    }

    // Methods
    public function isHighSeverity(): bool
    {
        return $this->weight >= 4;
    }

    public function isMediumSeverity(): bool
    {
        return $this->weight >= 2 && $this->weight <= 3;
    }

    public function isLowSeverity(): bool
    {
        return $this->weight <= 1;
    }

    public function setWeight(int $weight): void
    {
        $this->extra_value = (string) $weight;
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
    const STANDARD_IMPACTS = [
        [
            'name' => 'Danno alla reputazione',
            'description' => "Perdita di fiducia pubblica e danno all'immagine aziendale",
            'extra_value' => '4',
        ],
        [
            'name' => 'Perdita finanziaria',
            'description' => "Impatto economico diretto o indiretto sull'organizzazione",
            'extra_value' => '4',
        ],
        [
            'name' => 'Violazione della privacy',
            'description' => 'Accesso non autorizzato a dati personali e sensibili',
            'extra_value' => '5',
        ],
        [
            'name' => 'Danno fisico o materiale',
            'description' => 'Lesioni personali o danni a proprietà fisiche',
            'extra_value' => '5',
        ],
        [
            'name' => "Interruzione dell'attività",
            'description' => 'Sospensione o compromissione dei processi operativi',
            'extra_value' => '3',
        ],
        [
            'name' => 'Violazione della compliance',
            'description' => 'Non conformità a normative legali e regolamentari',
            'extra_value' => '3',
        ],
        [
            'name' => 'Perdita di integrità dei dati',
            'description' => 'Modifica non autorizzata o corruzione di informazioni',
            'extra_value' => '3',
        ],
        [
            'name' => 'Perdita di disponibilità',
            'description' => 'Mancata accessibilità ai dati o ai servizi quando necessario',
            'extra_value' => '2',
        ],
        [
            'name' => 'Discriminazione o ingiustizia',
            'description' => 'Trattamento inequale basato su dati personali',
            'extra_value' => '4',
        ],
        [
            'name' => 'Limitazione dei diritti',
            'description' => "Restrizione dell'esercizio dei diritti degli interessati",
            'extra_value' => '3',
        ],
    ];

    const ISO_27005_IMPACTS = [
        [
            'name' => 'Business disruption',
            'description' => 'Interruzione dei processi business critici',
            'extra_value' => 'ISO27005-001',
        ],
        [
            'name' => 'Financial loss',
            'description' => 'Perdite finanziarie dirette o indirette',
            'extra_value' => 'ISO27005-002',
        ],
        [
            'name' => 'Reputational damage',
            'description' => "Danno all'immagine e alla reputazione",
            'extra_value' => 'ISO27005-003',
        ],
        [
            'name' => 'Legal and regulatory sanctions',
            'description' => 'Sanzioni legali e violazioni normative',
            'extra_value' => 'ISO27005-004',
        ],
        [
            'name' => 'Loss of competitive advantage',
            'description' => 'Perdita di vantaggio competitivo sul mercato',
            'extra_value' => 'ISO27005-005',
        ],
    ];

    public static function getStandardImpacts(): array
    {
        return self::STANDARD_IMPACTS;
    }

    public static function getIsoImpacts(): array
    {
        return self::ISO_27005_IMPACTS;
    }

    public static function getAllImpacts(): array
    {
        return array_merge(self::STANDARD_IMPACTS, self::ISO_27005_IMPACTS);
    }

    public static function getImpactOptions(): array
    {
        return collect(self::getAllImpacts())
            ->pluck('name', 'name')
            ->toArray();
    }

    public static function findByWeight(int $weight): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('extra_value', $weight)->get();
    }

    public static function findByIsoCode(string $isoCode): ?self
    {
        return static::where('extra_value', $isoCode)->first();
    }
}
