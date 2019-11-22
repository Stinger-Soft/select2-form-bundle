<?php

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\Expr\Select;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use StingerSoft\Select2FormBundle\DataTransformer\OneEntityToIdTransformer;
use StingerSoft\Select2FormBundle\Tests\BaseDoctrineTest;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableSingleIdentity;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableMultiIdentities;

class OneEntityToIdTransformerTest extends AbstractTransformerTest {

	public function testConstructSingleIdentity(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$transformer = new OneEntityToIdTransformer($em, SelectableSingleIdentity::class, $qb);
		$this->assertNotNull($transformer);
	}

	public function testConstructMultiIdentities(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$transformer = new OneEntityToIdTransformer($em, SelectableMultiIdentities::class, $qb);
		$this->assertNotNull($transformer);
	}

	/**
	 * @throws MappingException
	 */
	public function testTransformSingleIdentity(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$transformer = new OneEntityToIdTransformer($em, SelectableSingleIdentity::class, $qb);
		$id = $transformer->transform(new SelectableSingleIdentity(12));
		$this->assertNotNull($id);
		$this->assertEquals(12, $id);
	}

	public function testReverseTransformSingleIdentity(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$meta = $this->mockSelectableClassMetadata();
		$em->method('getRepository')->willReturn($this->mockRepository($em, $meta));

		$transformer = new OneEntityToIdTransformer($em, SelectableSingleIdentity::class, $qb);
		/** @var SelectableSingleIdentity $selectable */
		$selectable = $transformer->reverseTransform(12);
		$this->assertNotNull($selectable);
		$this->assertInstanceOf(SelectableSingleIdentity::class, $selectable);
		$this->assertEquals(12, $selectable->getId());
	}

	/**
	 * @throws MappingException
	 */
	public function testTransformMultiIdentities(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$transformer = new OneEntityToIdTransformer($em, SelectableMultiIdentities::class, $qb);
		$id = $transformer->transform(new SelectableMultiIdentities(12, 'test'));
		$this->assertNotNull($id);
		$this->assertEquals(12, $id);
	}

	public function testReverseTransformMultiIdentities(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$meta = $this->mockSelectableMultiKeyClassMetadata();
		$em->method('getRepository')->willReturn($this->mockRepository($em, $meta));

		$transformer = new OneEntityToIdTransformer($em, SelectableMultiIdentities::class, $qb);
		/** @var SelectableSingleIdentity $selectable */
		$selectable = $transformer->reverseTransform([12, 'test']);
		$this->assertNotNull($selectable);
		$this->assertInstanceOf(SelectableMultiIdentities::class, $selectable);
		$this->assertEquals(12, $selectable->getId());
		$this->assertEquals('test', $selectable->getTitle());
	}

	protected function mockRepository($em, $metaData): MockObject {
		$repos = $this->getMockBuilder(EntityRepository::class)->setConstructorArgs([
			$em,
			$metaData,
		])->setMethods([
			'find',
		])->getMock();

		$repos->method('find')->willReturnCallback(static function ($id) {
			if(is_array($id)) {
				return new SelectableMultiIdentities($id[0], $id[1]);
			}
			return new SelectableSingleIdentity($id);
		});

		return $repos;
	}

} 