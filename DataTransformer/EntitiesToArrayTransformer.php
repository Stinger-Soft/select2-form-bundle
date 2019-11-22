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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use RuntimeException;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

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
	 * @var ClassMetadata
	 */
	private $classMetadata;

	/**
	 * Constructor
	 *
	 * @param ChoiceLoaderInterface  $choiceList
	 * @param EntityManagerInterface $em
	 * @param string                 $class
	 */
	public function __construct(ChoiceLoaderInterface $choiceList, EntityManagerInterface $em, $class) {
		$this->choiceList = $choiceList;
		$this->em = $em;
		$this->class = $class;
		$this->classMetadata = $em->getClassMetadata($class);
	}

	/**
	 * Returns the identifier values of the given entity
	 *
	 * @param object $entity
	 * @return array
	 * @throws RuntimeException If object is no managed entity an exception is thrown
	 */
	private function getIdentifierValues($entity): array {
		if(!$this->em->contains($entity)) {
			throw new RuntimeException('Entities passed to the choice field must be managed. Maybe ' . 'persist them in the entity manager?');
		}

		$this->em->initializeObject($entity);

		return $this->classMetadata->getIdentifierValues($entity);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function transform($collection) {
		if(null === $collection) {
			return [];
		}

		if(!($collection instanceof Collection) && !is_array($collection)) {
			throw new UnexpectedTypeException($collection, 'Doctrine\Common\Collections\Collection instead of ' . get_class($collection));
		}

		$array = [];

		$identifiers = $this->em->getClassMetadata($this->class)->getIdentifierFieldNames();
		if(count($identifiers) > 1) {
			foreach($collection as $entity) {
				$value = $this->getIdentifierValues($entity);
				$array[] = json_encode($value);
			}
		} else {
			foreach($collection as $entity) {
				$value = current($this->getIdentifierValues($entity));
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

		$identifierFieldNames = $this->classMetadata->getIdentifierFieldNames();

		if(count($keys) === 1 && strpos($keys[0], ',') >= 0) {
			if(count($identifierFieldNames) > 1) {
				$keys = explode('},{', $keys[0]);
			} else {
				$keys = explode(',', $keys[0]);
			}
		}

		$notFound = [];

		if(count($identifierFieldNames) === 1) {
			$entities = $this->em->getRepository($this->class)->findBy([$identifierFieldNames[0] => $keys]);
			if(count($entities)) {
				foreach($entities as $entity) {
					$collection->add($entity);
				}
			}
		} else {
			$size = count($keys);
			foreach($keys as $index => $key) {
				if(is_string($key)) {
					if($size > 1) {
						if($index === 0) {
							$key .= '}';
						} else if($index === $size - 1) {
							$key = '{' . $key;
						} else {
							$key = '{' . $key . '}';
						}
					}
					$realKey = json_decode($key, true);
				} else {
					$realKey = $key;
				}
				$entity = $this->em->getRepository($this->class)->find($realKey);
				if($entity) {
					$collection->add($entity);
				}
			}
		}

		if(count($notFound) > 0) {
			throw new TransformationFailedException(sprintf('The entities with keys "%s" could not be found', implode('", "', $notFound)));
		}
		return $collection;
	}
}