<?php

/*
 * This file is part of the Stinger Soft Select2 Form Bundle.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\Select2FormBundle\ChoiceLoader;

use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;

class EntityFieldChoiceLoader implements ChoiceLoaderInterface {

	/**
	 * @var string[]
	 */
	protected array $tags = array();

	/** @var ChoiceListInterface|null */
	private ChoiceListInterface|null $choiceList = null;

	/**
	 * Creates the choice loader with the given parameters
	 *
	 * @param Registry      $registry
	 *            Doctrine Registry
	 * @param class-string  $class
	 *            Class to fetch the property from
	 * @param array|string  $property
	 *            The name of the property
	 * @param null|callable $modifier
	 *            Callable to modifiy the query builder
	 * @param bool|string   $searchPropertyDelimiter
	 *            If a string is given, it will be used to split/explode the values of the property
	 */
	public function __construct(Registry $registry, string $class, array|string $property, callable|null $modifier, bool|string $splitSearchProperty) {
		// get entity manager for the specified class
		$em = $registry->getManagerForClass($class);
		$this->generateChoiceList($em, $class, $property, $modifier, $splitSearchProperty);
	}

	/**
	 *
	 * @param EntityManager  $em
	 * @param class-string   $class
	 *            Class to fetch the property from
	 * @param array|string   $property
	 * @param null|callable  $modifier
	 *            Callable to modifiy the query builder
	 * @param bool|string $searchPropertyDelimiter
	 *            If a string is given, it will be used to split/explode the values of the property
	 */
	protected function generateChoiceList(EntityManager $em, string $class, string|array $property, callable|null $modifier, bool|string $searchPropertyDelimiter): void {
		// Fetch all possible values, yeah might get huge, but therefor this type has the word 'sync' in its name
		$qb = $em->getRepository($class)
			->createQueryBuilder('item')
			->distinct(true);

		if(is_string($property)) {
			$qb->select('item.' . $property)
				->where('item.' . $property . ' IS NOT NULL')
				->orderBy('item.' . $property, 'ASC');
		}

		if(is_array($property)) {
			$qb->select('item.' . $property[0]) // runtime complexity in O(1)
			->where('item.' . $property[0] . ' IS NOT NULL');

			// Merge all results if more than one column is used
			if(count($property) > 1) {
				for($i = 1, $iMax = count($property); $i < $iMax; $i++) {
					if($property[$i]) {
						$qb->addSelect('item.' . $property[$i])
							->orWhere('item.' . $property[$i] . ' IS  NOT NULL');
					}
				}
			}
		}

		if($modifier && is_callable($modifier)) {
			$qb = $modifier($qb);
		}
		$scalarResults = $qb->getQuery()->getScalarResult();
		$choices = array_map('current', $scalarResults);
		if(is_array($property)) {
			foreach($scalarResults as $scalarResult) {
				for($i = 1, $intMax = count($property); $i < $intMax; $i++) {
					$choices[] = $scalarResult[$property[$i]];
				}
			}
		}
		foreach($choices as $choice) {
			if(!trim($choice))
				continue;
			if($searchPropertyDelimiter) {
				foreach(explode($searchPropertyDelimiter, $choice) as $subChoice) {
					$this->tags[$subChoice] = $subChoice;
				}
			} else {
				$this->tags[$choice] = $choice;
			}
		}
		if($searchPropertyDelimiter) {
			ksort($this->tags, SORT_FLAG_CASE | SORT_NATURAL);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface::loadChoiceList()
	 */
	public function loadChoiceList($value = null): ChoiceListInterface {
		// is called on form view create after loadValuesForChoices of form create
		if($this->choiceList instanceof ChoiceListInterface) {
			return $this->choiceList;
		}

		// if no values preset yet return empty list
		$this->choiceList = new ArrayChoiceList($this->tags, $value);

		return $this->choiceList;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface::loadChoicesForValues()
	 */
	public function loadChoicesForValues(array $values, $value = null): array {
		$choices = array();
		foreach($values as $key => $valueItem) {
			$choices[$key] = $valueItem;
		}
		return $choices;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface::loadValuesForChoices()
	 */
	public function loadValuesForChoices(array $choices, $value = null): array {
		$values = array();
		foreach($choices as $key => $valueItem) {
			$values[$key] = $valueItem;
		}
		return $values;
	}
}