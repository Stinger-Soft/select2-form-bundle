<?php

namespace StingerSoft\Select2FormBundle\Tests\Entity;

class SelectableSingleIdentity {

	/**
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 *
	 * @var string|null
	 */
	private $title;

	public function __construct(?int $id) {
		$this->id = $id;
	}

	/**
	 *
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 *
	 * @return string|null
	 */
	public function getTitle(): ?string {
		return $this->title;
	}

	/**
	 *
	 * @param string|null $title
	 * @return SelectableSingleIdentity
	 */
	public function setTitle(?string $title): SelectableSingleIdentity {
		$this->title = $title;
		return $this;
	}
}