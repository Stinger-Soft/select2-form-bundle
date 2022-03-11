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

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;

class EntityFieldChoiceLoader implements ChoiceLoaderInterface {

	protected $tags = [];

	/** @var ChoiceListInterface */
	private $choiceList;

	/**
	 * Creates the choice loader with the given parameters
	 *
	 * @param ManagerRegistry $registry
	 *            Doctrine Registry
	 * @param string          $class
	 *            Class to fetch the property from
	 * @param string          $property
	 *            The name of the property
	 * @param null|callable   $modifier
	 *            Callable to modifiy the query builder
	 * @param boolean|string  $searchPropertyDelimiter
	 *            If a string is given, it will be used to split/explode the values of the property
	 */
	public function __construct(ManagerRegistry $registry, $class, $property, $modifier, $splitSearchProperty) {
		// get entity manager for the specified class
		$em = $registry->getManagerForClass($class);
		$this->generateChoiceList($em, $class, $property, $modifier, $splitSearchProperty);
	}

	/**
	 *
	 * @param EntityManager  $em
	 * @param string         $class
	 *            Class to fetch the property from
	 * @param string         $property
	 *            The name of the property
	 * @param null|callable  $modifier
	 *            Callable to modifiy the query builder
	 * @param boolean|string $searchPropertyDelimiter
	 *            If a string is given, it will be used to split/explode the values of the property
	 */
	protected function generateChoiceList(ObjectManager $em, $class, $property, $modifier, $searchPropertyDelimiter) {
		// Fetch all possible values, yeah might get huge, but therefor this type has the word 'sync' in its name
		$qb = $em->getRepository($class)
			->createQueryBuilder('item')
			->select('item.' . $property)
			->where('item.' . $property . ' IS NOT NULL')
			->distinct(true)
			->orderBy('item.' . $property, 'ASC');

		if($modifier && is_callable($modifier)) {
			$qb = $modifier($qb);
		}
		$choices = $qb->getQuery()->getScalarResult();

		$choices = array_map('current', $choices);
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
	public function loadChoiceList($value = null) {
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
	public function loadChoicesForValues(array $values, $value = null) {
		$choices = [];
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
	public function loadValuesForChoices(array $choices, $value = null) {
		$values = [];
		foreach($choices as $key => $valueItem) {
			$values[$key] = $valueItem;
		}
		return $values;
	}
}
