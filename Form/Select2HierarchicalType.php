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

namespace StingerSoft\Select2FormBundle\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 */
class Select2HierarchicalType extends Select2BaseType {

	/**
	 * @var PropertyAccessor
	 */
	protected $propertyAccessor;

	/**
	 *
	 * @param RouterInterface     $router
	 * @param TranslatorInterface $translator
	 * @param ManagerRegistry     $managerRegistry
	 */
	public function __construct(RouterInterface $router, TranslatorInterface $translator, ManagerRegistry $managerRegistry) {
		parent::__construct($router, $translator, $managerRegistry);
		$this->propertyAccessor = PropertyAccess::createPropertyAccessor();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::buildView()
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		parent::buildView($view, $form, $options);

		/**
		 *
		 * @var ChoiceListInterface $choiceList
		 */
		$choiceList = $options['choice_list'] ?? $options['choice_loader']->loadChoiceList();

		$data = [];
		foreach($choiceList->getChoices() as $choice) {
			$level = $this->propertyAccessor->getValue($choice, $options['level_path']);
			if($level === 0) {
				$root = $this->propertyAccessor->getValue($choice, $options['root_path']);
				if(is_object($root)) {
					$root = $this->propertyAccessor->getValue($choice, 'id');
				}
				$data[$root] = $this->createDataArray($choice, $options, 0);
				if($options['select_leafs_only']) {
					$this->addChildren($choice, $data[$root], $choiceList->getChoices(), $options, 1);
				} else {
					$this->addChildren($choice, $data[$root], $choiceList->getChoices(), $options, 1, '_children');
					$this->addChildrenFlat($choice, $data, $choiceList->getChoices(), $options, 1, true);
					$view->vars['select2OptionsJavaScript']['templateResult'] = 'StingerSoftSelect2.templateResult.hierarchical';
				}
				if($options['remove_disabled_paths']) {
					$this->removeDeadNodes($data, $root);
				}
			}
		}
		$view->vars['hierarchicalData'] = array_values($data);
		$view->vars['selectLeafsOnly'] = $options['select_leafs_only'];
	}

	/**
	 * XXX: Use StingerSoft PhpCommons after the next release
	 *
	 * @param array $array
	 * @param array $path
	 * @param
	 *            $callback
	 * @return array
	 */
	public static function applyCallbackByPath(array &$array, array $path, $callback): ?array {
		$i = 0;
		while($i < count($path) - 1) {
			$piece = $path[$i];
			if(!is_array($array) || !array_key_exists($piece, $array)) {
				return null;
			}
			$array = &$array[$piece];
			$i++;
		}
		$piece = end($path);
		call_user_func_array($callback, [
			&$array,
			$piece,
		]);
		return $array;
	}

	/**
	 *
	 * @param array  $data
	 * @param string $root
	 */
	protected function removeDeadNodes(array &$data, $root): void {
		$removeParts = [];
		$this->findDeadNodes($data, $root, [], $removeParts);

		foreach($removeParts as $removePart) {
			self::applyCallbackByPath($data, $removePart, function (&$array, $key) {
				unset($array[$key]);
			});
		}

		foreach($removeParts as $removePart) {
			$resetParts = [];
			foreach($removePart as $resetPart) {
				$resetParts[] = $resetPart;
				if($resetPart === 'children') {
					self::applyCallbackByPath($data, $resetParts, function (&$array, $key) {
						$array[$key] = array_values($array[$key]);
					});
				}
			}
		}
	}

	protected function findDeadNodes(array &$data, $key, array $path = [], array &$removeParts = []): bool {
		$path[] = $key;
		$item = $data[$key];
		$disabled = array_key_exists('disabled', $item) && $item['disabled'];
		$childCount = array_key_exists('children', $item) ? count($item['children']) : 0;
		if($childCount > 0) {
			$path[] = 'children';
			$allChildrenRemoved = true;
			foreach(array_keys($item['children']) as $cKey) {
				$allChildrenRemoved &= $this->findDeadNodes($item['children'], $cKey, $path, $removeParts);
			}
			if($allChildrenRemoved && $disabled) {
				$removeParts[] = array_slice($path, 0, -1);
			}
		}
		if($childCount == 0 && $disabled) {
			$removeParts[] = $path;
			return true;
		}
		return false;
	}

	protected function createDataArray($entityChoice, array $options, $level, array $parentData = null) {
		if(isset($options['choice_label']) && $options['choice_label']) {
			if(is_string($options['choice_label'])) {
				$label = $this->propertyAccessor->getValue($entityChoice, $options['choice_label']);
			} else if(is_callable($options['choice_label'])) {
				$callback = $options['choice_label'];
				$label = $callback($entityChoice, $entityChoice->getId(), null);
			}
		} else {
			$label = $entityChoice->__toString();
		}
		$result = [
			'id'        => $entityChoice->getId(),
			'text'      => $label,
			'level'     => $level,
			'path_text' => $parentData && array_key_exists('path_text', $parentData) ? $parentData['path_text'] . $options['path_separator'] . $label : $label,
		];
		if(isset($options['choice_attr']) && $options['choice_attr']) {
			if(is_array($options['choice_attr'])) {
				$result = array_merge($result, $options['choice_attr']);
			} else if(is_callable($options['choice_attr'])) {
				$callback = $options['choice_attr'];
				$result = array_merge($result, $callback($entityChoice, $entityChoice->getId(), null));
			}
		}
		return $result;
	}

	/**
	 *
	 * @param object $entityChoice
	 * @param array  $data
	 * @param array  $choices
	 * @param array  $options
	 * @param int    $level
	 * @param bool   $inclFullChildren
	 */
	protected function addChildrenFlat($entityChoice, array &$data, array $choices, array $options, $level, $inclFullChildren = false): void {
		$children = $this->propertyAccessor->getValue($entityChoice, $options['children_path']);
		foreach($children as $child) {
			if(array_key_exists($child->getId(), $choices)) {
				$childData = $this->createDataArray($child, $options, $level, $data[$entityChoice->getId()]);
				$data[$child->getId()] = $childData;
				$this->addChildrenFlat($child, $data, $choices, $options, $level + 1, $inclFullChildren);
				if($inclFullChildren) {
					$this->addChildren($child, $data[$child->getId()], $choices, $options, $level + 1, '_children');
				} else {
					if(!isset($data[$entityChoice->getId()]['_children'])) {
						$data[$entityChoice->getId()]['_children'] = [];
					}
					$data[$entityChoice->getId()]['_children'][] = $childData;
				}
			}
		}
	}

	/**
	 *
	 * @param object $entityChoice
	 * @param array  $data
	 * @param array  $choices
	 * @param array  $options
	 * @param int    $level
	 * @param string $childrenOption
	 */
	protected function addChildren($entityChoice, array &$data, array $choices, array $options, $level, $childrenOption = 'children'): void {
		$children = $this->propertyAccessor->getValue($entityChoice, $options['children_path']);
		foreach($children as $child) {
			if(array_key_exists($child->getId(), $choices)) {
				$childData = $this->createDataArray($child, $options, $level, $data);
				$this->addChildren($child, $childData, $choices, $options, $level + 1, $childrenOption);
				if(!isset($data[$childrenOption])) {
					$data[$childrenOption] = [];
				}
				$data[$childrenOption][] = $childData;
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::buildForm()
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->setAttribute('children_path', $options['children_path']);
		$builder->setAttribute('root_path', $options['root_path']);
		$builder->setAttribute('level_path', $options['level_path']);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\Select2FormBundle\Form\Select2BaseType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefault('children_path', 'children');
		$resolver->addAllowedTypes('children_path', 'string');
		$resolver->setDefault('root_path', 'root');
		$resolver->addAllowedTypes('root_path', 'string');
		$resolver->setDefault('level_path', 'lvl');
		$resolver->addAllowedTypes('level_path', 'string');
		$resolver->setDefault('remove_disabled_paths', false);
		$resolver->addAllowedTypes('remove_disabled_paths', 'boolean');
		$resolver->setDefault('path_separator', ' - ');
		$resolver->addAllowedTypes('path_separator', 'string');
		$resolver->setDefault('select_leafs_only', true);
		$resolver->addAllowedTypes('select_leafs_only', 'boolean');
		$resolver->setDefault('hideSelected', false);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getParent()
	 */
	public function getParent() {
		return Select2EntityType::class;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\FormTypeInterface::getName()
	 */
	public function getName(): string {
		return $this->getBlockPrefix();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
	 */
	public function getBlockPrefix() {
		return 'stinger_soft_select2_form_hierarchical';
	}
}
