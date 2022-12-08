<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    private $em;
    private $repository;

    public function __construct(EntityManagerInterface $entityManagerInterface, PostRepository $postRepository)
    {
        $this->repository = $postRepository;
        $this->em = $entityManagerInterface;
    }

    #[Route('/lista', name: 'posts')]
    public function index(): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $this->repository->findAll()
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'post_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $post = $this->repository->find($id);

        if (is_null($post)) {
            $this->addFlash(
                'errors',
                'Post #' . $id . ' is not found.'
            );
    
            return $this->redirectToRoute('posts');
        }

        $this->em->remove($post);
        $this->em->flush();

        $this->addFlash(
            'info',
            'Post #' . $id . ' was deleted.'
        );

        return $this->redirectToRoute('posts');
    }
}
