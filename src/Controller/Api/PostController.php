<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializerBuilder;
use App\Entity\Post;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Media;
use DateTime;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Exception;

class PostController extends AbstractController
{
    /**
     * Show single post
     *
     * @SWG\Response(
     *     response=200,
     *     description="Post found; returns post object"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Post not found"
     * )
     *
     * @Route("/api/post/{postId}", name="api_post_show", methods={"GET"})
     * @param $postId
     * @return Response
     */
    public function show($postId)
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $post = $repository->find($postId);

        if (!$post) {
            return new JsonResponse([
                "message" => "Post not found"
            ], Response::HTTP_NOT_FOUND);
        }

        $serializer = SerializerBuilder::create()->build();
        $postArray = $serializer->toArray($post);
        return new JsonResponse($postArray);
    }

    /**
     * List all posts
     *
     * @Route("/api/post", name="api_post_list", methods={"GET"})
     * @return Response
     */
    public function list()
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $posts = $repository->findAll();

        $serializer = SerializerBuilder::create()->build();
        $postArray = $serializer->toArray($posts);
        return new JsonResponse($postArray);
    }

    /**
     * Create post
     *
     * @Route("/api/post", name="api_post_create", methods={"POST"})
     * @Security(name="Bearer")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Post object",
     *     required=true,
     *     @SWG\Schema(
     *        type="object",
     *        @SWG\Property(property="title", type="string", example="Post title"),
     *        @SWG\Property(property="content", type="string", example="Lorem ipsum dolor..."),
     *        @SWG\Property(property="image", type="integer", example=32),
     *        @SWG\Property(property="categories", type="array", @SWG\Items(
     *          type="integer",
     *          example=3
     *        )),
     *        @SWG\Property(property="tags", type="array", @SWG\Items(
     *          type="string",
     *          example="my tag"
     *        ))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Post created, returns array with message and created object"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid data, post was not created"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     * @throws Exception
     */
    public function create(Request $request, ValidatorInterface $validator)
    {
        return $this->upsert($request, $validator);
    }

    /**
     * Update post
     *
     * @Route("/api/post/{postId}", name="api_post_update", methods={"PUT"})
     * @Security(name="Bearer")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Post object",
     *     required=true,
     *     @SWG\Schema(
     *        type="object",
     *        @SWG\Property(property="title", type="string", example="Post title"),
     *        @SWG\Property(property="content", type="string", example="Lorem ipsum dolor..."),
     *        @SWG\Property(property="image", type="integer", example=32),
     *        @SWG\Property(property="categories", type="array", @SWG\Items(
     *          type="integer",
     *          example=3
     *        )),
     *        @SWG\Property(property="tags", type="array", @SWG\Items(
     *          type="string",
     *          example="my tag"
     *        ))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns array with message and updated object"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid data, post was not updated"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Post not found"
     * )
     *
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param null $postId
     * @return Response
     * @throws Exception
     */
    public function update(Request $request, ValidatorInterface $validator, $postId = null)
    {
        return $this->upsert($request, $validator, $postId);
    }

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param null $postId
     * @return JsonResponse
     * @throws Exception
     */
    private function upsert(Request $request, ValidatorInterface $validator, $postId = null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Post::class);

        if ($postId) {
            $post = $repository->find($postId);

            if (empty($post))
            {
                $response = [
                    "message" => "Post not found"
                ];
                return new JsonResponse($response, Response::HTTP_NOT_FOUND);
            }
        } else {
            $post = new Post();
        }

        $post->setAuthor($this->getUser());
        $post->setDate(new DateTime());
        $post->setTitle($request->get('title'));
        $post->setContent($request->get('content'));

        $mediaId = $request->get('image');
        if (!empty($mediaId)) {
            $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
            $media = $mediaRepository->find($mediaId);
            if (empty($media)) {
                $response = [
                    "message" => "Media does not exist: $mediaId"
                ];
                return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
            }
            $post->setImage($media);
        }

        $categories = $request->get('categories', []);
        $categoriesRepository = $this->getDoctrine()->getRepository(Category::class);
        $post->clearCategories();
        foreach($categories as $categoryId) {
            $category = $categoriesRepository->find($categoryId);

            if (empty($category)) {
                $response = [
                    "message" => "Category does not exist: $categoryId"
                ];
                return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
            }

            $post->addCategory($category);
        }

        $tags = $request->get('tags', []);
        $tagsRepository = $this->getDoctrine()->getRepository(Tag::class);
        $post->clearTags();
        foreach($tags as $tagName) {
            $tag = $tagsRepository->findOneBy(["name" => $tagName]);

            if (empty($tag)) {
                $tag = new Tag();
                $tag->setName($tagName);
                $entityManager->persist($tag);
            }

            $post->addTag($tag);
        }

        $errors = $validator->validate($post);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new JsonResponse([
                "message" => $errorsString
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($post);
        $entityManager->flush();

        $serializer = SerializerBuilder::create()->build();
        $postArray = $serializer->toArray($post);

        return new JsonResponse([
            "message" => $postId ? "Post updated" : "Post created",
            "result" => $postArray
        ],$postId ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    /**
     * Delete post
     *
     * @Route("/api/post/{postId}", name="api_post_delete", methods={"DELETE"})
     * @Security(name="Bearer")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Post removed"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Post not found"
     * )
     *
     * @param $postId
     * @return Response
     */
    public function delete($postId)
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $post = $repository->find($postId);

        if (empty($post))
        {
            $response = [
                "message" => "Post not found"
            ];
            return new JsonResponse($response, Response::HTTP_NOT_FOUND);
        }

        $em=$this->getDoctrine()->getManager();
        $em->remove($post);
        $em->flush();

        return new JsonResponse([
            "message" => "Post deleted"
        ],Response::HTTP_OK);
    }
}
