<?php

namespace FmTod\LaravelTabulator\Concerns;

use FmTod\LaravelTabulator\Contracts\RendersTable;
use FmTod\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait HasTabulatorTable
{
    abstract public static function tabulatorClass(): string;

    public static function tabulatorTable(?Request $request = null): TabulatorTable
    {
        return app(self::tabulatorClass(), $request ? [$request] : []);
    }

    public static function tabulatorRender(string $view, array $data = [], RendersTable|string|null $renderer = null): mixed
    {
        return self::tabulatorTable()->render($view, $data, $renderer);
    }

    public static function tabulatorRules(string $arrayKey = null, string $keyName = null): array
    {
        /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model */
        $model = self::newModelInstance();
        $table = $model->getTable();

        if (! $keyName) {
            $keyName = $model->getKeyName();
        }

        if (! $arrayKey) {
            $arrayKey = Str::singular($table).'_'.Str::plural($keyName);
        }

        return [
            'type' => ['required', 'string', 'in:array,query'],

            $arrayKey => ['prohibited_if:type,query', 'array'],
            "$arrayKey.*" => ['prohibited_if:type,query', 'integer', "exists:$table,$keyName"],

            'filters' => ['prohibited_if:type,array', 'array'],
            'sort' => ['prohibited_if:type,array', 'array'],
        ];
    }

    public static function tabulatorQuery(Request $request, string $arrayKey = null, string $keyName = null): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model */
        $model = self::newModelInstance();
        $table = self::tabulatorTable($request);

        if (! $keyName) {
            $keyName = $model->getKeyName();
        }

        if (! $arrayKey) {
            $arrayKey = Str::singular($model->getTable()).'_'.Str::plural($keyName);
        }

        $validated = $request->validate(self::tabulatorRules($arrayKey, $keyName));

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $validated['type'] === 'array'
            ? $model->whereIn($keyName, $validated[$arrayKey])
            : $table->getScopedQuery();

        return $query;
    }
}
