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

class PostController extends AbstractController
{
    /**
     * @Route("/api/post/{postId}", name="api_post_show", methods={"GET"})
     * @param $postId
     * @return Response
     */
    public function show($postId)
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $post = $repository->find($postId);

        $serializer = SerializerBuilder::create()->build();
        $postArray = $serializer->toArray($post);
        return new JsonResponse($postArray);
    }

    /**
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
     * @Route("/api/post", name="api_post_create", methods={"POST"})
     * @Route("/api/post/{postId}", name="api_post_update", methods={"PUT"})
     * @param Request $request
     * @param $postId
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function upsert(Request $request, ValidatorInterface $validator, $postId = null)
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
                    "message" => "Media not found: $mediaId"
                ];
                return new JsonResponse($response, Response::HTTP_NOT_FOUND);
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
                    "message" => "Category not found: $categoryId"
                ];
                return new JsonResponse($response, Response::HTTP_NOT_FOUND);
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

        $entityManager->persist($post);
        $entityManager->flush();

        $serializer = SerializerBuilder::create()->build();
        $postArray = $serializer->toArray($post);

        return new JsonResponse([
            "message" => $postId ? "Post updated" : "Post created",
            "result" => $postArray
        ],Response::HTTP_OK);
    }

    /**
     * @Route("/api/post/{postId}", name="api_post_delete", methods={"DELETE"})
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
