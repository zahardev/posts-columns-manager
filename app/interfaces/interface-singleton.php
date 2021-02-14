<?php

namespace PCM\Interfaces;

interface Singleton {
	public function init();

	public static function instance();
}
