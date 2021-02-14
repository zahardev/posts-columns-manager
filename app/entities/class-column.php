<?php

namespace PCM\Entities;

/**
 * Class Column
 * @package PCM\Entities
 */
class Column {

    /**
     * string @var
     */
    public $name;

    /**
     * string @var
     */
    public $label;

    /**
     * mixed @var
     */
    public $data;

    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $label
     * @param string $data
     */
    public function __construct( $name = '', $label = '', $data = '' ) {
        $this->name  = $name;
        $this->label = $label;
    }
}