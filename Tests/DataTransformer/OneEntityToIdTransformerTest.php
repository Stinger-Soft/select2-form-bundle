<?php

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use StingerSoft\Select2FormBundle\DataTransformer\OneEntityToIdTransformer;
use StingerSoft\Select2FormBundle\Tests\BaseDoctrineTest;
use StingerSoft\Select2FormBundle\Tests\Entity\Selectable;

class OneEntityToIdTransformerTest extends BaseDoctrineTest {

	public function testConstruct(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$transformer = new OneEntityToIdTransformer($em, 'StingerSoft\Select2FormBundle\Tests\Entity\Selectable', $qb);
		$this->assertNotNull($transformer);
	}

	public function testTransform(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$transformer = new OneEntityToIdTransformer($em, 'StingerSoft\Select2FormBundle\Tests\Entity\Selectable', $qb);
		$id = $transformer->transform(new Selectable(12));
		$this->assertNotNull($id);
		$this->assertEquals(12, $id);
	}

	public function testReverseTransform(): void {
		$em = $this->mockEntityManager();
		$qb = $this->mockQueryBuilder($em);
		$meta = $this->mockClassMetadata();
		$em->method('getRepository')->will($this->returnValue($this->mockRepository($em, $meta)));

		$transformer = new OneEntityToIdTransformer($em, 'StingerSoft\Select2FormBundle\Tests\Entity\Selectable', $qb);
		/** @var Selectable $selectable */
		$selectable = $transformer->reverseTransform(12);
		$this->assertNotNull($selectable);
		$this->assertInstanceOf(Selectable::class, $selectable);
		$this->assertEquals(12, $selectable->getId());
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
		$em->method('getClassMetaData')->willReturn($this->mockClassMetadata());
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
			return new Selectable($id);
		});

		return $repos;
	}

	protected function mockClassMetadata(): MockObject {
		$cm = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->setMethods([
			'getReflectionClass',
			'getSingleIdentifierFieldName',
			'getIdentifierValues',
		])->setConstructorArgs([
			'Select2FormBundle:Selectable',
		])->getMock();
		$cm->method('getReflectionClass')->willReturn(new \ReflectionClass(Selectable::class));
		$cm->method('getSingleIdentifierFieldName')->willReturn('id');
		$cm->method('getIdentifierValues')->willReturnCallback(function (Selectable $s) {
			return [
				'id' => $s->getId(),
			];
		});
		$cm->namespace = 'Pec\Select2FormBundle\Tests\Entity\Selectable';
		return $cm;
	}
} 