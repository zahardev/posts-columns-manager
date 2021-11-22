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
	 * string @var
	 */
	public $source;

    /**
     * mixed @var
     */
    public $data;

	/**
	 * Field constructor.
	 *
	 * @param string $name
	 * @param string $label
	 * @param string $source
	 * @param string $data
	 */
	public function __construct( $name = '', $label = '', $source = '', $data = '' ) {
		$this->name   = $name;
		$this->label  = $label;
		$this->source = $source;
		$this->data   = $data;

		return apply_filters( 'pcm_column', $this );
	}
}
