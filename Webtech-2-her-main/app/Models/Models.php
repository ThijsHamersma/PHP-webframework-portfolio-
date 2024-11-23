<?php
namespace App\Models;
abstract class Models
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hasMany = [];
    protected array $belongsTo = [];
    protected array $belongsToMany = [];

    public function __construct(string $table, string $primaryKey, array $fillable)
    {
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->fillable = $fillable;
    }


    public function getRows(): array
    {
        return $this->fillable;
    }

    public function getName(): string
    {
        return $this->table;
}

    public function belongsTo(string $relatedModel, string $foreignKey = null): void
    {
        $this->belongsTo[$relatedModel] = $foreignKey ?? strtolower($relatedModel) . '_id';
    }

    public function hasMany(string $relatedModel, string $foreignKey = null): void
    {
        $relatedModelBaseName = basename(str_replace('\\', '/', $relatedModel));
        $this->hasMany[$relatedModelBaseName] = $foreignKey ?? strtolower($relatedModelBaseName) . '_id';
    }

    public function getBelongsTo(): array
    {
        return $this->belongsTo;
    }

    public function getHasMany(): array
    {
        return $this->hasMany;
    }

    public function belongsToMany(string $relatedModel, string $junctionTable, string $foreignKey = null, string $relatedKey = null): void
    {
        $relatedModelBaseName = basename(str_replace('\\', '/', $relatedModel));
        $this->belongsToMany[$relatedModelBaseName] = [
            'junction_table' => $junctionTable,
            'foreign_key' => $foreignKey ?? strtolower($relatedModelBaseName) . '_id',
            'related_key' => $relatedKey ?? strtolower($this->getName()) . '_id',
        ];
    }
    public function getBelongsToMany(): array
    {
        return $this->belongsToMany;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }


}
