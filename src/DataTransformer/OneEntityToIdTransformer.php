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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between an entity and its id (PK)
 */
class OneEntityToIdTransformer implements DataTransformerInterface {

	/**
	 * The classname of the entity to be handled
	 *
	 * @var string
	 */
	private string $class;

	/**
	 * A query builder or closure to reverse transform
	 *
	 * @var QueryBuilder|\Closure|null
	 */
	private QueryBuilder|\Closure|null $queryBuilder;

	/**
	 *
	 * @param EntityManager $em
	 * @param string|null $class
	 * @param QueryBuilder|\Closure|null $queryBuilder
	 * @throws UnexpectedTypeException
	 */
	public function __construct(protected EntityManager $em, string|null $class, QueryBuilder|\Closure|null $queryBuilder) {
		if (!(null === $queryBuilder || $queryBuilder instanceof \Closure || $queryBuilder instanceof \Doctrine\ORM\QueryBuilder)) {
			throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
		}

		if (null == $class) {
			throw new UnexpectedTypeException($class, 'string');
		}

		$this->class = $class;
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Form\DataTransformerInterface::transform()
	 */
	public function transform($data): mixed {
		if (null === $data) {
			return null;
		}

		$meta = $this->em->getClassMetadata($this->class);
		$this->em->initializeObject($data);

		if (!$meta->getReflectionClass()->isInstance($data)) {
			throw new TransformationFailedException('Invalid data, must be an instance of ' . $this->class . ' found ' . get_class($data));
		}

		$identifierField = $meta->getSingleIdentifierFieldName();
		$values = $meta->getIdentifierValues($data);
		$id = null;
		if (array_key_exists($identifierField, $values)) {
			$id = $values[$identifierField];
		}
		return is_numeric($id) ? $id . "" : $id;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Form\DataTransformerInterface::reverseTransform()
	 */
	public function reverseTransform($data) {
		if (!$data) {
			return null;
		}

		$em = $this->em;
		$class = $this->class;
		$repository = $em->getRepository($class);

		if ($qb = $this->queryBuilder) {
			// If a closure was passed, call id with the repository and the id
			if ($qb instanceof \Closure) {
				$qb = $qb($repository, $data);
			}

			try {
				$result = $repository->find($data);
			} catch (NoResultException $e) {
				$result = null;
			}
		} else {
			// Defaults to find()
			$result = $repository->find($data);
		}

		if (!$result)
			throw new TransformationFailedException('Can not find entity');

		return $result;
	}
}