<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializerBuilder;
use App\Entity\Category;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

class CategoryController extends AbstractController
{
    /**
     * Show single category
     *
     * @SWG\Response(
     *     response=200,
     *     description="Category found; returns category object"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Category not found"
     * )
     *
     * @Route("/api/category/{categoryId}", name="api_category_show", methods={"GET"})
     * @param $categoryId
     * @return Response
     */
    public function show($categoryId)
    {
        $repository = $this->getDoctrine()->getRepository(Category::class);
        $category = $repository->find($categoryId);

        if (!$category) {
            return new JsonResponse([
                "message" => "Category not found"
            ], Response::HTTP_NOT_FOUND);
        }

        $serializer = SerializerBuilder::create()->build();
        $categoryArray = $serializer->toArray($category);
        return new JsonResponse($categoryArray);
    }

    /**
     * List all categories
     *
     * @Route("/api/category", name="api_category_list", methods={"GET"})
     * @return Response
     */
    public function list()
    {
        $repository = $this->getDoctrine()->getRepository(Category::class);
        $categories = $repository->findAll();

        $serializer = SerializerBuilder::create()->build();
        $categoriesArray = $serializer->toArray($categories);
        return new JsonResponse($categoriesArray);
    }

    /**
     * Create category
     *
     * @Route("/api/category", name="api_category_create", methods={"POST"})
     * Update category
     *
     *
     * @Security(name="Bearer")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Category object",
     *     required=true,
     *     @SWG\Schema(
     *        type="object",
     *        @SWG\Property(property="name", type="string"),
     *        @SWG\Property(property="description", type="string")
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns array with message and created object"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid data, category was not created"
     * )
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param null $categoryId
     * @return JsonResponse
     */
    public function create(Request $request, ValidatorInterface $validator)
    {
        return $this->upsert($request, $validator);
    }

    /**
     * Update category
     *
     * @Route("/api/category/{categoryId}", name="api_category_update", methods={"PUT"})
     *
     * @Security(name="Bearer")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Category object",
     *     required=true,
     *     @SWG\Schema(
     *        type="object",
     *        @SWG\Property(property="name", type="string"),
     *        @SWG\Property(property="description", type="string")
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns array with message and updated object"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid data or category not found"
     * )
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param null $categoryId
     * @return JsonResponse
     */
    public function update(Request $request, ValidatorInterface $validator, $categoryId)
    {
        return $this->upsert($request, $validator, $categoryId);
    }

    private function upsert(Request $request, ValidatorInterface $validator, $categoryId = null)
    {
        $repository = $this->getDoctrine()->getRepository(Category::class);

        if ($categoryId) {
            $category = $repository->find($categoryId);

            if (empty($category))
            {
                $response = [
                    "message" => "Category not found"
                ];
                return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
            }
        } else {
            $category = new Category();
        }

        $category->setName($request->get('name'));
        $category->setDescription($request->get('description'));

        $errors = $validator->validate($category);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new JsonResponse([
                "message" => $errorsString
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($category);
        $entityManager->flush();

        $serializer = SerializerBuilder::create()->build();
        $categoryArray = $serializer->toArray($category);

        return new JsonResponse([
            "message" => $categoryId ? "Category updated" : "Category created",
            "result" => $categoryArray
        ],Response::HTTP_OK);
    }

    /**
     * Delete category
     *
     * @Route("/api/category/{categoryId}", name="api_category_delete", methods={"DELETE"})
     * @Security(name="Bearer")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Category removed"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Category not found"
     * )
     *
     * @param $categoryId
     * @return Response
     */
    public function delete($categoryId)
    {
        $repository = $this->getDoctrine()->getRepository(Category::class);
        $category = $repository->find($categoryId);

        if (empty($category))
        {
            $response = [
                "message" => "Category not found"
            ];
            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        $em=$this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        return new JsonResponse([
            "message" => "Category deleted"
        ],Response::HTTP_OK);
    }
}
