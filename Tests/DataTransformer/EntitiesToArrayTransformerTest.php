<?php

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use StingerSoft\Select2FormBundle\DataTransformer\EntitiesToArrayTransformer;
use StingerSoft\Select2FormBundle\Tests\BaseDoctrineTest;
use StingerSoft\Select2FormBundle\Tests\Entity\Selectable;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class EntitiesToArrayTransformerTest extends BaseDoctrineTest {

	/**
	 *
	 * @return \StingerSoft\Select2FormBundle\Tests\Entity\Selectable[]
	 */
	protected function getSelectables() {
		return array(
			new Selectable(12),
			new Selectable(13),
			new Selectable(14),
			new Selectable(15) 
		);
	}

	/**
	 *
	 * @param Selectable[] $selectables        	
	 * @return integer[]
	 */
	protected function getIds(array $selectables) {
		$ids = array();
		foreach($selectables as $selectable) {
			$ids[] = $selectable->getId();
		}
		return $ids;
	}

	/**
	 *
	 * @param Selectable $s        	
	 * @return integer[]
	 */
	protected function getIdentifierValues(Selectable $s) {
		return array(
			'id' => $s->getId() 
		);
	}

	/**
	 *
	 * @param Selectable[] $selectables        	
	 * @return ChoiceLoaderInterface
	 */
	protected function getChoiceList(array $selectables) {
		return new CallbackChoiceLoader(function () use ($selectables) {
			return $selectables;
		});
	}

	/**
	 *
	 * @param Selectable[] $selectables        	
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	protected function getCollection(array $selectables) {
		return new ArrayCollection($selectables);
	}

	public function testConstruct() {
		$em = $this->mockEntityManager();
		$class = Selectable::class;
		$choiceList = $this->getChoiceList($this->getSelectables());
		new EntitiesToArrayTransformer($choiceList, $em, $class);
		$this->assertTrue(true);
	}

	public function testTransform() {
		$selectables = $this->getSelectables();
		$em = $this->mockEntityManager();
		$class = Selectable::class;
		$choiceList = $this->getChoiceList($selectables);
		
		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$ids = $transformer->transform($this->getCollection($selectables));
		$this->assertNotNull($ids);
		$this->assertNotEmpty($ids);
		$this->assertCount(count($selectables), $ids);
		foreach($ids as $id) {
			$this->assertTrue(count(array_filter($selectables, function ($selectable) use ($id) {
				return $selectable->getId() == $id;
			})) == 1);
		}
		
		// test with an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList(array()), $em, $class);
		$ids = $transformer->transform($this->getCollection($selectables));
		$this->assertNotNull($ids);
		$this->assertNotEmpty($ids);
		$this->assertCount(count($selectables), $ids);
		foreach($ids as $id) {
			$this->assertTrue(count(array_filter($selectables, function ($selectable) use ($id) {
				return $selectable->getId() == $id;
			})) == 1);
		}
	}

	public function testReverseTransform() {
		$selectables = $this->getSelectables();
		$em = $this->mockEntityManager();
		$meta = $this->mockClassMetadata();
		$em->method('getRepository')->will($this->returnValue($this->mockRepository($em, $meta)));
		$class = Selectable::class;
		$choiceList = $this->getChoiceList($selectables);
		$ids = $this->getIds($selectables);
		
		// test with an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform($ids);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertTrue(count(array_filter($ids, function ($id) use ($selectable) {
				return $selectable->getId() == $id;
			})) == 1);
		}
		
		// test with an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList(array()), $em, $class);
		$selectables2 = $transformer->reverseTransform($ids);
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertTrue(count(array_filter($ids, function ($id) use ($selectable) {
				return $selectable->getId() == $id;
			})) == 1);
		}
		
		// test with single value array, containing comma separated ids an an initially filled list
		$transformer = new EntitiesToArrayTransformer($choiceList, $em, $class);
		$selectables2 = $transformer->reverseTransform(array(
			join(',', $ids) 
		));
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertTrue(count(array_filter($ids, function ($id) use ($selectable) {
				return $selectable->getId() == $id;
			})) == 1);
		}
		
		// test with single value array, containing comma separated ids an an initially empty list
		$transformer = new EntitiesToArrayTransformer($this->getChoiceList(array()), $em, $class);
		$selectables2 = $transformer->reverseTransform(array(
			join(',', $ids) 
		));
		$this->assertNotNull($selectables2);
		$this->assertNotEmpty($selectables2);
		$this->assertCount(count($selectables2), $ids);
		foreach($selectables as $selectable) {
			$this->assertTrue(count(array_filter($ids, function ($id) use ($selectable) {
				return $selectable->getId() == $id;
			})) == 1);
		}
	}

	protected function mockClassMetadata() {
		$cm = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->setMethods(array(
			'getReflectionClass',
			'getSingleIdentifierFieldName',
			'getIdentifierValues' 
		))->setConstructorArgs(array(
			'Select2FormBundle:Selectable' 
		))->getMock();
		$cm->method('getReflectionClass')->will($this->returnValue(new \ReflectionClass(Selectable::class)));
		$cm->method('getSingleIdentifierFieldName')->will($this->returnValue('id'));
		$cm->method('getIdentifierValues')->will($this->returnCallback(function (Selectable $s) {
			return array(
				'id' => $s->getId() 
			);
		}));
		$cm->namespace = 'Pec\Select2FormBundle\Tests\Entity\Selectable';
		return $cm;
	}

	/**
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|EntityManager
	 */
	protected function mockEntityManager() {
		$em = $this->getMockBuilder(EntityManager::class)->setMethods(array(
			'getClassMetaData',
			'initializeObject',
			'getRepository',
			'contains' 
		))->disableOriginalConstructor()->getMockForAbstractClass();
		$em->method('contains')->will($this->returnValue(true));
		$em->method('getClassMetaData')->will($this->returnValue($this->mockClassMetadata()));
		return $em;
	}

	protected function mockRepository($em, $metaData) {
		$repos = $this->getMockBuilder(EntityRepository::class)->setConstructorArgs(array(
			$em,
			$metaData 
		))->setMethods(array(
			'findOneById' 
		))->getMock();
		
		$repos->method('findOneById')->will($this->returnCallback(function ($id) {
			return new Selectable($id);
		}));
		
		return $repos;
	}
}