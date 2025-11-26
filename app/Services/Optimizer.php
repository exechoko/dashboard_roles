<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class Optimizer
{
    protected $query;
    protected $cacheKey = null;
    protected $cacheTTL = null;

    public function __construct($model)
    {
        $this->query = is_string($model) ? $model::query() : $model;
    }

    public static function model($model)
    {
        return new static($model);
    }

    // ============================================
    // MÉTODOS DE CARGA
    // ============================================

    /**
     * Eager Loading - Evita N+1
     */
    public function with(...$relations)
    {
        $this->query = $this->query->with($relations);
        return $this;
    }

    /**
     * Contar relaciones sin cargarlas
     */
    public function withCount(...$relations)
    {
        $this->query = $this->query->withCount($relations);
        return $this;
    }

    /**
     * Select específico - Solo columnas necesarias
     */
    public function only(...$columns)
    {
        $this->query = $this->query->select($columns);
        return $this;
    }

    // ============================================
    // FILTROS
    // ============================================

    /**
     * Where simple
     */
    public function where($column, $operator = null, $value = null)
    {
        $this->query = $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Where con callback (para queries complejas)
     */
    public function whereCallback($callback)
    {
        $this->query = $this->query->where($callback);
        return $this;
    }

    /**
     * WhereHas para relaciones
     */
    public function whereHas($relation, $callback)
    {
        $this->query = $this->query->whereHas($relation, $callback);
        return $this;
    }

    /**
     * OrWhereHas para relaciones
     */
    public function orWhereHas($relation, $callback)
    {
        $this->query = $this->query->orWhereHas($relation, $callback);
        return $this;
    }

    /**
     * WhereIn
     */
    public function whereIn($column, $values)
    {
        $this->query = $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * WhereBetween
     */
    public function whereBetween($column, $values)
    {
        $this->query = $this->query->whereBetween($column, $values);
        return $this;
    }

    /**
     * WhereNull
     */
    public function whereNull($column)
    {
        $this->query = $this->query->whereNull($column);
        return $this;
    }

    /**
     * WhereNotNull
     */
    public function whereNotNull($column)
    {
        $this->query = $this->query->whereNotNull($column);
        return $this;
    }

    /**
     * When - Aplicar condición solo si se cumple
     */
    public function when($condition, $callback)
    {
        $this->query = $this->query->when($condition, $callback);
        return $this;
    }

    // ============================================
    // ORDENAMIENTO Y AGRUPAMIENTO
    // ============================================

    /**
     * OrderBy
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->query = $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Latest (más reciente)
     */
    public function latest($column = 'created_at')
    {
        $this->query = $this->query->latest($column);
        return $this;
    }

    /**
     * Oldest (más antiguo)
     */
    public function oldest($column = 'created_at')
    {
        $this->query = $this->query->oldest($column);
        return $this;
    }

    /**
     * GroupBy
     */
    public function groupBy(...$columns)
    {
        $this->query = $this->query->groupBy($columns);
        return $this;
    }

    // ============================================
    // CACHÉ
    // ============================================

    /**
     * Cachear resultados
     */
    public function cached($key, $minutes = 60)
    {
        $this->cacheKey = $key;
        $this->cacheTTL = $minutes * 60;
        return $this;
    }

    /**
     * Limpiar caché específico
     */
    public static function clearCache($key)
    {
        Cache::forget($key);
    }

    // ============================================
    // EJECUCIÓN
    // ============================================

    /**
     * Obtener todos los resultados
     */
    public function get()
    {
        if ($this->cacheKey) {
            return Cache::remember($this->cacheKey, $this->cacheTTL, fn() => $this->query->get());
        }
        return $this->query->get();
    }

    /**
     * Paginación
     */
    public function paginate($perPage = 15)
    {
        // No cachear paginación (varía por página)
        return $this->query->paginate($perPage);
    }

    /**
     * Simple Paginate (más rápido, sin contador total)
     */
    public function simplePaginate($perPage = 15)
    {
        return $this->query->simplePaginate($perPage);
    }

    /**
     * Primer resultado
     */
    public function first()
    {
        if ($this->cacheKey) {
            return Cache::remember($this->cacheKey, $this->cacheTTL, fn() => $this->query->first());
        }
        return $this->query->first();
    }

    /**
     * Contar resultados
     */
    public function count()
    {
        if ($this->cacheKey) {
            return Cache::remember($this->cacheKey, $this->cacheTTL, fn() => $this->query->count());
        }
        return $this->query->count();
    }

    /**
     * Chunk - Procesar en lotes
     */
    public function chunk($size, $callback)
    {
        return $this->query->chunk($size, $callback);
    }

    /**
     * Lazy - Streaming de resultados
     */
    public function lazy($chunkSize = 1000)
    {
        return $this->query->lazy($chunkSize);
    }

    /**
     * Pluck - Extraer columna específica
     */
    public function pluck($column, $key = null)
    {
        if ($this->cacheKey) {
            return Cache::remember($this->cacheKey, $this->cacheTTL, fn() => $this->query->pluck($column, $key));
        }
        return $this->query->pluck($column, $key);
    }

    /**
     * Exists - Verificar si existe
     */
    public function exists()
    {
        return $this->query->exists();
    }

    // ============================================
    // UTILIDADES
    // ============================================

    /**
     * Acceso directo al query builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Obtener SQL generado (para debug)
     */
    public function toSql()
    {
        return $this->query->toSql();
    }

    /**
     * Obtener SQL con bindings (para debug)
     */
    public function dump()
    {
        dd($this->query->toSql(), $this->query->getBindings());
    }
}
