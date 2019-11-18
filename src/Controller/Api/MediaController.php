<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use JMS\Serializer\SerializerBuilder;
use App\Entity\Media;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

class MediaController extends AbstractController
{
    /**
     * Show single media object
     *
     * @SWG\Response(
     *     response=200,
     *     description="Media found; returns media object"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Media not found"
     * )
     *
     * @Route("/api/media/{mediaId}", name="api_media_show", methods={"GET"})
     * @param $mediaId
     * @return Response
     */
    public function show($mediaId)
    {
        $repository = $this->getDoctrine()->getRepository(Media::class);
        $media = $repository->find($mediaId);

        if (!$media) {
            return new JsonResponse([
                "message" => "Media not found"
            ], Response::HTTP_NOT_FOUND);
        }

        $serializer = SerializerBuilder::create()->build();
        $mediaArray = $serializer->toArray($media);
        return new JsonResponse($mediaArray);
    }

    /**
     * Show all media objects
     *
     * @Route("/api/media", name="api_media_list", methods={"GET"})
     * @return Response
     */
    public function list()
    {
        $repository = $this->getDoctrine()->getRepository(Media::class);
        $media = $repository->findAll();

        $serializer = SerializerBuilder::create()->build();
        $mediaArray = $serializer->toArray($media);
        return new JsonResponse($mediaArray);
    }

    /**
     * Create new media from file (requires binary data)
     *
     * @Route("/api/media", name="api_media_create", methods={"POST"})
     *
     * @Security(name="Bearer")
     *
     * @SWG\Response(
     *     response=201,
     *     description="Media object successfully created"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Cannot create media object"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function create(Request $request, ValidatorInterface $validator)
    {
        $content = $request->getContent();

        if (empty($content)) {
            return new JsonResponse([
                "error" => "Binary data is required"
            ], Response::HTTP_BAD_REQUEST);
        }

        $fileData = $this->uploadFile($content);

        if (isset($fileData['error'])) {
            return new JsonResponse([
                "error" => $fileData['error']
            ], Response::HTTP_BAD_REQUEST);
        }

        $media = new Media();
        $media->setName($fileData["name"]);
        $errors = $validator->validate($media);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new JsonResponse([
                "message" => $errorsString
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($media);
        $entityManager->flush();

        $serializer = SerializerBuilder::create()->build();
        $mediaArray = $serializer->toArray($media);

        return new JsonResponse([
            "message" => "Media created",
            "result" => $mediaArray
        ],Response::HTTP_CREATED);
    }

    /**
     * Delete media object
     *
     * @Route("/api/media/{mediaId}", name="api_media_delete", methods={"DELETE"})
     *
     * @Security(name="Bearer")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Media removed"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Media not found"
     * )
     *
     * @param $mediaId
     * @return Response
     */
    public function delete($mediaId)
    {
        $repository = $this->getDoctrine()->getRepository(Media::class);
        $media = $repository->find($mediaId);

        if (empty($media))
        {
            $response = [
                "message" => "Media not found"
            ];
            return new JsonResponse($response, Response::HTTP_NOT_FOUND);
        }

        $em=$this->getDoctrine()->getManager();
        $em->remove($media);
        $em->flush();

        return new JsonResponse([
            "message" => "Media deleted"
        ],Response::HTTP_OK);
    }

    /**
     * @param $content
     * @return array
     */
    private function uploadFile($content)
    {
        $filename = $this->generateUniqueFileName();
        $file = fopen($this->getParameter('media_directory') . "/" .$filename . "tmp", "w");

        if ($file === false) {
            return [
                "error" => "File can not be opened"
            ];
        }

        $path = stream_get_meta_data($file)['uri'];
        file_put_contents($path, $content);
        fclose($file);

        $uploadedFile = new UploadedFile($path, $path, null, null, null, true);

        $fileName = $this->generateUniqueFileName().'.'.$uploadedFile->guessExtension();

        $uploadedFile->move(
            $this->getParameter('media_directory'),
            $fileName
        );

        return [
            "name" => $fileName
        ];
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
