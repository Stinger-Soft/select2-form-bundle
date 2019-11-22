<?php
/*
 * This file is part of the PEC Platform select2-form-bundle.
 *
 * (c) PEC project engineers &amp; consultants
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\Select2FormBundle\Tests\Entity;

class SelectableMultiIdentities {

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

	public function __construct(?int $id, ?string $title) {
		$this->id = $id;
		$this->title = $title;
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
	 * @return SelectableMultiIdentities
	 */
	public function setTitle(?string $title): SelectableMultiIdentities {
		$this->title = $title;
		return $this;
	}
}