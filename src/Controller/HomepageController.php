<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class HomepageController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index() {
        return new Response(
            '<html><body>Homepage loaded successfully</body></html>'
        );
    }
}