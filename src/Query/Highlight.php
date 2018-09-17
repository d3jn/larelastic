<?php

namespace D3jn\Larelastic\Query;

class Highlight
{
    /**
     * Highlight parameters for elasticsearch query.
     *
     * @var array
     */
    protected $params;

    /**
     * Array of fields for highlighting.
     *
     * @var \D3jn\Larelastic\Query\HighlightField[]
     */
    protected $fields = [];

    /**
     * Get raw array of request parameters.
     *
     * @return array
     */
    public function getRaw(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[$field->getName()] = (object) $field->getRaw();
        }

        return compact('fields');
    }

    /**
     * Create new field to highlight.
     *
     * @param string $name
     *
     * @return \D3jn\Larelastic\Query\HighlightField
     */
    public function field(string $name): HighlightField
    {
        if (! isset($this->fields[$name])) {
            $this->fields[$name] = app()->makeWith(HighlightField::class, [
                'highlight' => $this,
                'name' => $name
            ]);
        }

        return $this->fields[$name];
    }
}
