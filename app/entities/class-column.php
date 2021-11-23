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
    public $title;

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
	 * @param string $title
	 * @param string $source
	 * @param string $data
	 */
	public function __construct( $name = '', $title = '', $source = '', $data = '' ) {
		$this->name   = $name;
		$this->title  = $title;
		$this->source = $source;
		$this->data   = $data;

		return apply_filters( 'pcm_column', $this );
	}
}
