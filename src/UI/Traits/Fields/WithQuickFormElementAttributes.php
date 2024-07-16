<?php

declare(strict_types=1);

namespace MoonShine\UI\Traits\Fields;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Stringable;
use MoonShine\Contracts\UI\FieldContract;

/** @mixin FieldContract */
trait WithQuickFormElementAttributes
{
    protected ?string $wrapName = null;

    protected ?string $nameAttribute = null;

    public function setNameAttribute(string $name): static
    {
        $this->nameAttribute = $name;

        return $this;
    }

    public function getNameAttribute(string $index = null): string
    {
        return $this->prepareNameAttribute($index);
    }

    public function wrapName(string $wrapName): static
    {
        $this->wrapName = $wrapName;

        // because showWhen can be declared after
        if($this->showWhenState) {
            [$column, $value, $operator] = $this->showWhenData;

            $this->showWhenCondition = collect($this->showWhenCondition)
                ->reject(fn ($data, $index): bool => $data['object_id'] === spl_object_id($this))
                ->toArray();

            return $this->showWhen($column, $value, $operator);
        }

        return $this;
    }

    public function getWrapName(): ?string
    {
        return $this->wrapName;
    }

    protected function getNameUnDot(string $name): string
    {
        $parts = explode('.', $name);
        $count = count($parts);
        $result = $parts[0];

        for ($i = 1; $i < $count; $i++) {
            $result .= "[" . $parts[$i] . "]";
        }

        return $result;
    }

    protected function prepareNameAttribute($index = null, $wrap = null): string
    {
        $wrap ??= $this->wrapName;

        if ($this->nameAttribute) {
            return $this->nameAttribute;
        }

        return (string) str($this->getNameUnDot($this->getColumn()))
            ->when(
                ! is_null($wrap),
                static fn (Stringable $str): Stringable => $str->wrap("{$wrap}[", "]")
            )
            ->when(
                $this->isGroup() || $this->getAttribute('multiple'),
                static fn (Stringable $str): Stringable => $str->append(
                    "[" . ($index ?? '') . "]"
                )
            );
    }

    public function getNameDot(): string
    {
        $name = (string) str($this->getNameAttribute())->replace('[]', '');

        parse_str($name, $array);

        $result = collect(Arr::dot(array_filter($array)));

        return $result->isEmpty()
            ? $name
            : (string) str($result->keys()->first());
    }

    public function setId(string $id): static
    {
        $this->attributes->set('id', str($id)
            ->remove(['[', ']'])
            ->snake()
            ->value());

        return $this;
    }

    public function required(Closure|bool|null $condition = null): static
    {
        $this->setAttribute('required', value($condition, $this) ?? true);

        return $this;
    }

    public function disabled(Closure|bool|null $condition = null): static
    {
        $this->setAttribute('disabled', value($condition, $this) ?? true);

        return $this;
    }

    public function readonly(Closure|bool|null $condition = null): static
    {
        $this->setAttribute('readonly', value($condition, $this) ?? true);

        return $this;
    }
}
