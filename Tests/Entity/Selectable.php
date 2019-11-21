<?php

namespace StingerSoft\Select2FormBundle\Tests\Entity;

class Selectable {

	/**
	 *
	 * @var integer
	 */
	private $id;

	/**
	 *
	 * @var string
	 */
	private $title;

	public function __construct($id) {
		$this->id = $id;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 *
	 * @param string $title        	
	 * @return \StingerSoft\Select2FormBundle\Tests\Entity\Selectable
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}
}