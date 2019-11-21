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
namespace StingerSoft\Select2FormBundle\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Converts between an array of entities and an array of ids
 */
class EntitiesToArrayTransformer implements DataTransformerInterface {

	/**
	 * A list of choices
	 *
	 * @var ChoiceLoaderInterface
	 */
	private $choiceList;

	/**
	 * The doctrine entity manager
	 *
	 * @var EntityManager
	 */
	private $em;

	/**
	 * The name of the class
	 *
	 * @var string
	 */
	private $class;

	/**
	 * Metadata about the given class
	 *
	 * @var \Doctrine\ORM\Mapping\ClassMetadata
	 */
	private $classMetadata;

	/**
	 * Constructor
	 *
	 * @param ChoiceListInterface $choiceList        	
	 * @param EntityManager $em        	
	 * @param string $class        	
	 */
	public function __construct(ChoiceLoaderInterface $choiceList, EntityManager $em, $class) {
		$this->choiceList = $choiceList;
		$this->em = $em;
		$this->class = $class;
		$this->classMetadata = $em->getClassMetadata($class);
	}

	/**
	 * Returns the identifier values of the given entity
	 *
	 * @param object $entity        	
	 * @throws \Exception If object is no managed entity an exception is thrown
	 */
	private function getIdentifierValues($entity) {
		if(!$this->em->contains($entity)) {
			throw new \Exception('Entities passed to the choice field must be managed. Maybe ' . 'persist them in the entity manager?');
		}
		
		$this->em->initializeObject($entity);
		
		return $this->classMetadata->getIdentifierValues($entity);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Form\DataTransformerInterface::transform()
	 */
	public function transform($collection) {
		if(null === $collection) {
			return array();
		}
		
		if(!($collection instanceof Collection) && !is_array($collection)) {
			throw new UnexpectedTypeException($collection, 'Doctrine\Common\Collections\Collection instead of ' . get_class($collection));
		}
		
		$array = array();
		
		$identifiers = $this->em->getClassMetadata($this->class)->getIdentifierFieldNames();
		if(count($identifiers) > 1) {
			// load all choices
			$availableEntities = $this->choiceList->loadChoiceList();
			
			foreach($collection as $entity) {
				// identify choices by their collection key
				$key = array_search($entity, $availableEntities);
				$array[] = $key;
			}
		} else {
			foreach($collection as $entity) {
				$value = current($this->getIdentifierValues($entity));
				// $array[] = is_numeric($value) ? (int) $value : $value;
				$array[] = is_numeric($value) ? $value . '' : $value;
			}
		}
		return $array;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Form\DataTransformerInterface::reverseTransform()
	 */
	public function reverseTransform($keys) {
		$collection = new ArrayCollection();
		
		if('' === $keys || null === $keys) {
			return $collection;
		}
		
		if(!is_array($keys)) {
			throw new UnexpectedTypeException($keys, 'array');
		}
		
		if(count($keys) == 1 && strpos($keys[0], ',') >= 0) {
			$keys = explode(',', $keys[0]);
		}
		
		$notFound = array();
		
		// optimize this into a SELECT WHERE IN query
		foreach($keys as $key) {
			$entity = $this->em->getRepository($this->class)->findOneById($key);
			if($entity) {
				$collection->add($entity);
			}
		}
		
		if(count($notFound) > 0) {
			throw new TransformationFailedException(sprintf('The entities with keys "%s" could not be found', implode('", "', $notFound)));
		}
		return $collection;
	}
}