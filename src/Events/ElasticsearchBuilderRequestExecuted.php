<?php

namespace D3jn\Larelastic\Events;

class ElasticsearchBuilderRequestExecuted
{
    /**
     * Parameters of Elasticsearch request.
     *
     * @var array
     */
    public $parameters;

    /**
     * Result of Elasticsearch request.
     *
     * @var array
     */
    public $result;

    /**
     * Create a new event instance.
     *
     * @param array $parameters
     * @param array $result
     */
    public function __construct(array $parameters, array $result)
    {
        $this->parameters = $parameters;
        $this->result = $result;
    }
}
