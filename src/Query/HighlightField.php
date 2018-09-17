<?php

namespace D3jn\Larelastic\Query;

class HighlightField
{
    /**
     * Parent highlight object.
     *
     * @var \D3jn\Larelastic\Query\Highlight
     */
    protected $highlight;

    /**
     * Field name.
     *
     * @var string
     */
    protected $name;

    /**
     * Fragment size for this highlighted field.
     *
     * @var int|null
     */
    protected $fragmentSize = null;

    /**
     * Number of fragments for this highlighted field.
     *
     * @var int|null
     */
    protected $numberOfFragments = null;

    /**
     * Length of returned value if no matches our found.
     *
     * @var int|null
     */
    protected $noMatchSize = null;

    /**
     * Highlight constructor.
     *
     * @param string $name
     */
    public function __construct(Highlight $highlight, string $name)
    {
        $this->highlight = $highlight;
        $this->name = $name;
    }

    /**
     * Get raw array of request parameters.
     *
     * @return array
     */
    public function getRaw(): array
    {
        $params = [];

        if ($this->fragmentSize !== null) {
            $params['fragment_size'] = $this->fragmentSize;
        }

        if ($this->numberOfFragments !== null) {
            $params['number_of_fragments'] = $this->numberOfFragments;
        }

        if ($this->noMatchSize !== null) {
            $params['no_match_size'] = $this->noMatchSize;
        }

        return $params;
    }

    /**
     * Getter for name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set fragment size for this highlighted field.
     *
     * @param int $size
     *
     * @return $this
     */
    public function fragmentSize(int $size): HighlightField
    {
        $this->fragmentSize = $size;

        return $this;
    }

    /**
     * Set number of fragments for this highlighted field.
     *
     * @param int $number
     *
     * @return $this
     */
    public function numberOfFragments(int $number): HighlightField
    {
        $this->numberOfFragments = $number;

        return $this;
    }

    /**
     * Set length of returned value if no matches our found.
     *
     * @param int $size
     *
     * @return $this
     */
    public function noMatchSize(int $size): HighlightField
    {
        $this->noMatchSize = $size;

        return $this;
    }
}
