<?php

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\Expr\Select;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\MockObject;
use StingerSoft\Select2FormBundle\DataTransformer\OneEntityToIdTransformer;
use StingerSoft\Select2FormBundle\Tests\BaseDoctrineTest;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableSingleIdentity;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableMultiIdentities;

class OneEntityToIdTransformerTest extends BaseDoctrineTest {

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

	/**
	 *
	 * @return MockObject|EntityManager
	 */
	protected function mockEntityManager() {
		$em = $this->getMockBuilder(EntityManager::class)->setMethods([
			'getClassMetaData',
			'initializeObject',
			'getRepository',
		])->disableOriginalConstructor()->getMockForAbstractClass();
		$em
			->method('getClassMetaData')
			->willReturnCallback(function ($argument) {
				if($argument === SelectableSingleIdentity::class) {
					return $this->mockSelectableClassMetadata();
				}
				if($argument === SelectableMultiIdentities::class) {
					return $this->mockSelectableMultiKeyClassMetadata();
				}
				return null;
			});
		return $em;
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

	protected function mockSelectableClassMetadata(): MockObject {
		$identifier = ['id'];
		$cm = $this->getMockBuilder(ClassMetadata::class)->setMethods([
			'getReflectionClass',
			'getIdentifierFieldNames',
			'getSingleIdentifierFieldName',
			'getIdentifierValues',
		])->setConstructorArgs([
			SelectableSingleIdentity::class,
		])->getMock();
		$cm->method('getReflectionClass')->willReturn(new \ReflectionClass(SelectableSingleIdentity::class));
		$cm->method('getIdentifierFieldNames')->willReturn($identifier);
		$cm->method('getSingleIdentifierFieldName')->willReturn('id');
		$cm->method('getIdentifierValues')->willReturnCallback(static function (SelectableSingleIdentity $s) {
			return [
				'id' => $s->getId(),
			];
		});
		$cm->identifier = $identifier;
		$cm->namespace = 'Pec\Select2FormBundle\Tests\Entity\SelectableSingleIdentity';
		return $cm;
	}

	protected function mockSelectableMultiKeyClassMetadata(): MockObject {
		$identifier = ['id', 'title'];
		$cm = $this->getMockBuilder(ClassMetadata::class)->setMethods([
			'getReflectionClass',
			'getIdentifierFieldNames',
			'getIdentifierValues',
		])->setConstructorArgs([
			SelectableMultiIdentities::class,
		])->getMock();
		$cm->method('getReflectionClass')->willReturn(new \ReflectionClass(SelectableMultiIdentities::class));
		$cm->method('getIdentifierFieldNames')->willReturn($identifier);
		$cm->method('getIdentifierValues')->willReturnCallback(static function (SelectableMultiIdentities $s) {
			return [
				'id'    => $s->getId(),
				'title' => $s->getTitle(),
			];
		});
		$cm->identifier = $identifier;
		$cm->namespace = 'Pec\Select2FormBundle\Tests\Entity\SelectableMultiIdentities';
		return $cm;
	}

} 