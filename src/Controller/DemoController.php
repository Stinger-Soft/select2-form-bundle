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

namespace StingerSoft\Select2FormBundle\Controller;

use StingerSoft\Select2FormBundle\Form\DemoType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoController extends AbstractController {

	public function indexAction(Request $request): Response {
		$form = $this->createForm(DemoType::class);

		if ($request->isMethod('POST')) {
			$form->handleRequest($request);
		}

		return $this->render('@PecSelect2Form/Demo/index.html.twig', array(
			'form' => $form->createView()
		));
	}

	public function jsonAction(Request $request): JsonResponse {
		$result = array();
		foreach (DemoType::$testArray as $text => $id) {
			$result[] = array(
				'id' => $id,
				'text' => $text
			);
		}
		return new JsonResponse($result);
	}
}