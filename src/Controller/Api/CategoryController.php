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

class CategoryController extends AbstractController
{
    /**
     * @Route("/api/category/{categoryId}", name="api_category_show", methods={"GET"})
     * @param $categoryId
     * @return Response
     */
    public function show($categoryId)
    {
        $repository = $this->getDoctrine()->getRepository(Category::class);
        $category = $repository->find($categoryId);

        $serializer = SerializerBuilder::create()->build();
        $categoryArray = $serializer->toArray($category);
        return new JsonResponse($categoryArray);
    }

    /**
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
     * @Route("/api/category", name="api_category_create", methods={"POST"})
     * @Route("/api/category/{categoryId}", name="api_category_update", methods={"PUT"})
     * @param Request $request
     * @param $categoryId
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function upsert(Request $request, ValidatorInterface $validator, $categoryId = null)
    {
        $repository = $this->getDoctrine()->getRepository(Category::class);

        if ($categoryId) {
            $category = $repository->find($categoryId);

            if (empty($category))
            {
                $response = [
                    "message" => "Category not found"
                ];
                return new JsonResponse($response, Response::HTTP_NOT_FOUND);
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
     * @Route("/api/category/{categoryId}", name="api_category_delete", methods={"DELETE"})
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
            return new JsonResponse($response, Response::HTTP_NOT_FOUND);
        }

        $em=$this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        return new JsonResponse([
            "message" => "Category deleted"
        ],Response::HTTP_OK);
    }
}
