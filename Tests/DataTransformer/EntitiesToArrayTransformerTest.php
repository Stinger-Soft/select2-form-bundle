<?php

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use StingerSoft\Select2FormBundle\DataTransformer\EntitiesToArrayTransformer;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableMultiIdentities;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableSingleIdentity;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class EntitiesToArrayTransformerTest extends AbstractTransformerTest {

	public function testConstructSingleIdentity(): void {
		$em = $this->mockEntityManager();
		$class = SelectableSingleIdentity::class;
		$choiceList = $this->getChoiceList($this->getSelectablesSingleIdentity());
		new EntitiesToArrayTransformer($choiceList, $em, $class);
		$this->assertTrue(true);
	}

	/**
	 * @throws Exception
	 */
	public function testTransformSingleIdentity(): void {
		$selectables = $this->getSelectablesSingleIdentity();
		$em = $this->mockEntityManager();
		$class = SelectableSingleIdentity::class;
		$choiceList = $this->getChoiceList($selectables);

		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$this->validateSingleIdentityTransformation($transformer, $selectables);

		// test with an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$this->validateSingleIdentityTransformation($transformer, $selectables);
	}

	public function testReverseTransformSingleIdentity(): void {
		$selectables = $this->getSelectablesSingleIdentity();
		$em = $this->mockEntityManager();
		$meta = $this->mockSelectableClassMetadata();
		$em->method('getRepository')->willReturn($this->mockRepository($em, $meta));
		$class = SelectableSingleIdentity::class;
		$choiceList = $this->getChoiceList($selectables);
		$ids = $this->getSingleIdentityIds($selectables);

		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform($ids);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === (int)$id;
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
				return $selectable->getId() === (int)$id;
			})), 1);
		}

		// test with single value array, containing comma separated ids an an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform([
			implode(',', $ids),
		]);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === (int)$id;
			})), 1);
		}

		// test with single value array, containing comma separated ids an an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$selectables2 = $transformer->reverseTransform([
			implode(',', $ids),
		]);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === (int)$id;
			})), 1);
		}
	}

	/**
	 * @param EntitiesToArrayTransformer $transformer
	 * @param array                      $selectables
	 * @throws Exception
	 */
	protected function validateSingleIdentityTransformation(EntitiesToArrayTransformer $transformer, array $selectables): void {
		$ids = $transformer->transform($this->getCollection($selectables));
		$this->assertNotNull($ids);
		$this->assertNotEmpty($ids);
		$this->assertCount(count($selectables), $ids);
		foreach($ids as $id) {
			$this->assertEquals(count(array_filter($selectables, static function (SelectableSingleIdentity $selectable) use ($id) {
				return $selectable->getId() === (int)$id;
			})), 1);
		}
	}

	/**
	 *
	 * @return SelectableSingleIdentity[]
	 */
	protected function getSelectablesSingleIdentity(): array {
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
	protected function getSingleIdentityIds(array $selectables): array {
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
	protected function getSingleIdentityIdentifierValues(SelectableSingleIdentity $s): array {
		return [
			'id' => $s->getId(),
		];
	}

	/**
	 *
	 * @return SelectableSingleIdentity[]
	 */
	protected function getSelectablesMultipleIdentities(): array {
		return [
			new SelectableMultiIdentities(12, 'test'),
			new SelectableMultiIdentities(13, 'test'),
			new SelectableMultiIdentities(14, 'test'),
			new SelectableMultiIdentities(15, 'test'),
		];
	}

	/**
	 * @throws Exception
	 */
	public function testTransformMultiIdentities(): void {
		$selectables = $this->getSelectablesMultipleIdentities();
		$em = $this->mockEntityManager();
		$class = SelectableMultiIdentities::class;
		$choiceList = $this->getChoiceList($selectables);

		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$this->validateMultipleIdentityTransformation($transformer, $selectables);

		// test with an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$this->validateMultipleIdentityTransformation($transformer, $selectables);
	}

	public function testReverseTransformMultiIdentities(): void {
		$selectables = $this->getSelectablesMultipleIdentities();
		$em = $this->mockEntityManager();
		$meta = $this->mockSelectableMultiKeyClassMetadata();
		$em->method('getRepository')->willReturn($this->mockRepository($em, $meta));
		$class = SelectableMultiIdentities::class;
		$choiceList = $this->getChoiceList($selectables);
		$ids = $this->getMultiIdentityIds($selectables);

		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform($ids);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === (int)$id['id'] && $selectable->getTitle() === $id['title'];
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
				return $selectable->getId() === (int)$id['id'] && $selectable->getTitle() === $id['title'];
			})), 1);
		}

		// test with single value array, containing comma separated ids an an initially filled list
		$singleValue = [];
		foreach($ids as $id) {
			$singleValue[] = json_encode($id);
		}
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform([
			implode(',', $singleValue),
		]);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $singleValue);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === (int)$id['id'] && $selectable->getTitle() === $id['title'];
			})), 1);
		}

		// test with single value array, containing comma separated ids an an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList([]), $em, $class);
		$selectables2 = $transformer->reverseTransform([
			implode(',', $singleValue),
		]);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $singleValue);
		foreach($selectables as $selectable) {
			$this->assertEquals(count(array_filter($ids, static function ($id) use ($selectable) {
				return $selectable->getId() === (int)$id['id'] && $selectable->getTitle() === $id['title'];
			})), 1);
		}
	}

	/**
	 * @param EntitiesToArrayTransformer $transformer
	 * @param array                      $selectables
	 * @throws Exception
	 */
	protected function validateMultipleIdentityTransformation(EntitiesToArrayTransformer $transformer, array $selectables): void {
		$ids = $transformer->transform($this->getCollection($selectables));
		$this->assertNotNull($ids);
		$this->assertNotEmpty($ids);
		$this->assertCount(count($selectables), $ids);
		foreach($ids as $id) {
			$filteredSelectables = array_filter($selectables, static function (SelectableMultiIdentities $selectable) use ($id) {
				$realId = json_decode($id, true);
				return $selectable->getId() === (int)$realId['id'] && $selectable->getTitle() === $realId['title'];
			});
			$this->assertCount(1, $filteredSelectables);
		}
	}

	/**
	 *
	 * @param SelectableSingleIdentity[] $selectables
	 * @return integer[]
	 */
	protected function getMultiIdentityIds(array $selectables): array {
		$ids = [];
		foreach($selectables as $selectable) {
			$ids[] = [
				'id'    => $selectable->getId(),
				'title' => $selectable->getTitle(),
			];
		}
		return $ids;
	}

	/**
	 *
	 * @param SelectableMultiIdentities $s
	 * @return integer[]
	 */
	protected function getMultipleIdentityIdentifierValues(SelectableMultiIdentities $s): array {
		return [
			'id'    => $s->getId(),
			'title' => $s->getTitle(),
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
	 * @param SelectableSingleIdentity[]|SelectableMultiIdentities[] $selectables
	 * @return ArrayCollection
	 */
	protected function getCollection(array $selectables): ArrayCollection {
		return new ArrayCollection($selectables);
	}

	protected function mockRepository($em, $metaData): MockObject {
		$repos = $this->getMockBuilder(EntityRepository::class)->setConstructorArgs([
			$em,
			$metaData,
		])->setMethods([
			'find',
			'findBy',
		])->getMock();

		$repos->method('find')->willReturnCallback(static function ($id) {
			if(is_array($id)) {
				return new SelectableMultiIdentities($id['id'], $id['title']);
			}
			return new SelectableSingleIdentity($id);
		});
		$repos->method('findBy')->willReturnCallback(static function (array $findBy) {
			$result = [];
			foreach($findBy as $key => $values) {
				foreach($values as $value) {
					if(is_array($value)) {
						$result[] = new SelectableMultiIdentities($value['id'], $value['title']);
					} else {
						$result[] = new SelectableSingleIdentity($value);
					}
				}
			}
			return $result;
		});

		return $repos;
	}
}