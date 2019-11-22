<?php

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use StingerSoft\Select2FormBundle\DataTransformer\EntitiesToArrayTransformer;
use StingerSoft\Select2FormBundle\Tests\BaseDoctrineTest;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableSingleIdentity;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class EntitiesToArrayTransformerTest extends BaseDoctrineTest {

	/**
	 *
	 * @return SelectableSingleIdentity[]
	 */
	protected function getSelectables(): array {
		return [
			new SelectableSingleIdentity(12),
			new SelectableSingleIdentity(13),
			new SelectableSingleIdentity(14),
			new SelectableSingleIdentity(15),
		];
	}

	/**
	 *
	 * @param SelectableSingleIdentity[] $selectables
	 * @return integer[]
	 */
	protected function getIds(array $selectables): array {
		$ids = [];
		foreach($selectables as $selectable) {
			$ids[] = $selectable->getId();
		}
		return $ids;
	}

	/**
	 *
	 * @param SelectableSingleIdentity $s
	 * @return integer[]
	 */
	protected function getIdentifierValues(SelectableSingleIdentity $s): array {
		return [
			'id' => $s->getId(),
		];
	}

	/**
	 *
	 * @param SelectableSingleIdentity[] $selectables
	 * @return ChoiceLoaderInterface
	 */
	protected function getChoiceList(array $selectables): ChoiceLoaderInterface {
		return new CallbackChoiceLoader(static function () use ($selectables) {
			return $selectables;
		});
	}

	/**
	 *
	 * @param SelectableSingleIdentity[] $selectables
	 * @return ArrayCollection
	 */
	protected function getCollection(array $selectables): ArrayCollection {
		return new ArrayCollection($selectables);
	}

	public function testConstruct(): void {
		$em = $this->mockEntityManager();
		$class = SelectableSingleIdentity::class;
		$choiceList = $this->getChoiceList($this->getSelectables());
		new EntitiesToArrayTransformer($choiceList, $em, $class);
		$this->assertTrue(true);
	}

	public function testTransform(): void {
		$selectables = $this->getSelectables();
		$em = $this->mockEntityManager();
		$class = SelectableSingleIdentity::class;
		$choiceList = $this->getChoiceList($selectables);

		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$ids = $transformer->transform($this->getCollection($selectables));
		$this->assertNotNull($ids);
		$this->assertNotEmpty($ids);
		$this->assertCount(count($selectables), $ids);
		foreach($ids as $id) {
			$this->assertEquals(count(array_filter($selectables, static function (SelectableSingleIdentity $selectable) use ($id) {
				return $selectable->getId() === $id;
			})), 1);
		}

		// test with an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$ids = $transformer->transform($this->getCollection($selectables));
		$this->assertNotNull($ids);
		$this->assertNotEmpty($ids);
		$this->assertCount(count($selectables), $ids);
		foreach($ids as $id) {
			$this->assertEquals(count(array_filter($selectables, static function (SelectableSingleIdentity $selectable) use ($id) {
				return $selectable->getId() === $id;
			})), 1);
		}
	}

	public function testReverseTransform(): void {
		$selectables = $this->getSelectables();
		$em = $this->mockEntityManager();
		$meta = $this->mockClassMetadata();
		$em->method('getRepository')->willReturn($this->mockRepository($em, $meta));
		$class = SelectableSingleIdentity::class;
		$choiceList = $this->getChoiceList($selectables);
		$ids = $this->getIds($selectables);

		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform($ids);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === $id;
			})), 1);
		}

		// test with an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$selectables2 = $transformer->reverseTransform($ids);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === $id;
			})), 1);
		}

		// test with single value array, containing comma separated ids an an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform([
			join(',', $ids),
		]);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === $id;
			})), 1);
		}

		// test with single value array, containing comma separated ids an an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$selectables2 = $transformer->reverseTransform([
			join(',', $ids),
		]);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === $id;
			})), 1);
		}
	}

	protected function mockClassMetadata(): MockObject {
		$cm = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->setMethods([
			'getReflectionClass',
			'getSingleIdentifierFieldName',
			'getIdentifierValues',
		])->setConstructorArgs([
			'Select2FormBundle:Selectable',
		])->getMock();
		$cm->method('getReflectionClass')->willReturn(new ReflectionClass(SelectableSingleIdentity::class));
		$cm->method('getSingleIdentifierFieldName')->willReturn('id');
		$cm->method('getIdentifierValues')->willReturnCallback(static function (SelectableSingleIdentity $s) {
			return [
				'id' => $s->getId(),
			];
		});
		$cm->namespace = 'Pec\Select2FormBundle\Tests\Entity\Selectable';
		return $cm;
	}

	/**
	 *
	 * @return MockObject|EntityManagerInterface
	 */
	protected function mockEntityManager() {
		$em = $this->getMockBuilder(EntityManager::class)->setMethods([
			'getClassMetaData',
			'initializeObject',
			'getRepository',
			'contains',
		])->disableOriginalConstructor()->getMockForAbstractClass();
		$em->method('contains')->willReturn(true);
		$em->method('getClassMetaData')->willReturn($this->mockClassMetadata());
		return $em;
	}

	protected function mockRepository($em, $metaData): MockObject {
		$repos = $this->getMockBuilder(EntityRepository::class)->setConstructorArgs([
			$em,
			$metaData,
		])->setMethods([
			'findOneById',
		])->getMock();

		$repos->method('findOneById')->willReturnCallback(static function ($id) {
			return new SelectableSingleIdentity($id);
		});

		return $repos;
	}
}