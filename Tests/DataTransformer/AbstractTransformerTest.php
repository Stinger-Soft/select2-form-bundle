<?php
/*
 * This file is part of the PEC Platform select2-form-bundle.
 *
 * (c) PEC project engineers &amp; consultants
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\Select2FormBundle\Tests\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use StingerSoft\Select2FormBundle\Tests\BaseDoctrineTest;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableMultiIdentities;
use StingerSoft\Select2FormBundle\Tests\Entity\SelectableSingleIdentity;

abstract class AbstractTransformerTest extends BaseDoctrineTest {

	/**
	 *
	 * @param bool $withContains
	 * @return MockObject|EntityManager
	 */
	protected function mockEntityManager(bool $withContains = true) {
		$methods = [
			'getClassMetaData',
			'initializeObject',
			'getRepository',
		];
		if($withContains) {
			$methods[] = 'contains';
		}
		$em = $this->getMockBuilder(EntityManager::class)
			->setMethods($methods)
			->disableOriginalConstructor()
			->getMockForAbstractClass();
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
		if($withContains) {
			$em
				->method('contains')
				->willReturn(true);
		}
		return $em;
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
		$cm->method('getReflectionClass')->willReturn(new ReflectionClass(SelectableSingleIdentity::class));
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
		$cm->method('getReflectionClass')->willReturn(new ReflectionClass(SelectableMultiIdentities::class));
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