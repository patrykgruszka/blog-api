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

class MediaController extends AbstractController
{
    /**
     * @Route("/api/media/{mediaId}", name="api_media_show", methods={"GET"})
     * @param $mediaId
     * @return Response
     */
    public function show($mediaId)
    {
        $repository = $this->getDoctrine()->getRepository(Media::class);
        $media = $repository->find($mediaId);

        $serializer = SerializerBuilder::create()->build();
        $mediaArray = $serializer->toArray($media);
        return new JsonResponse($mediaArray);
    }

    /**
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
     * @Route("/api/media", name="api_media_create", methods={"POST"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function create(Request $request, ValidatorInterface $validator)
    {

        $content = $request->getContent();
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
     * @Route("/api/media/{mediaId}", name="api_media_delete", methods={"DELETE"})
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
