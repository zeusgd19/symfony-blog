<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentFormType;
use App\Form\PostFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PageController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        $repository = $doctrine->getRepository(Category::class);

        $categories = $repository->findAll();

        return $this->render('index.html.twig', ['categories' => $categories]);
    }


    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/single_post/{slug}', name: 'single_post')]
    public function singlePost(ManagerRegistry $doctrine, $slug,Request $request): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $post = $repository->findOneBy(['slug' => $slug]);
        $recents = $repository->findRecents();
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class,$comment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $comment = $form->getData();
            $comment->setPost($post);
            //Aumentamos en 1 el numero de comentarios del post
            $post->setNumComments($post->getNumComments() + 1);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            return $this->redirectToRoute('single_post',['slug' => $post->getSlug()]);
        }
        return $this->render('single_post.html.twig',[
            'post' => $post,
            'recents' => $recents,
            'commentForm' => $form->createView()
        ]);
    }

    #[Route('/blog/new',name:'new_post')]

    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger){
        $post = new Post();
        $form = $this->createForm(PostFormType::class,$post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $file = $form->get('image')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                // Move the file to the directory where images are stored
                try {

                    $file->move(
                        $this->getParameter('blog_directory'), $newFilename
                    );

                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                // updates the 'file$filename' property to store the PDF file name
                // instead of its contents
                $post->setImage($newFilename);
            }
            $post = $form->getData();
            $post->setSlug($slugger->slug($post->getTitle()));
            $post->setPostUser($this->getUser());
            $post->setNumLikes(0);
            $post->setNumComments(0);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('single_post',[
                'slug' => $post->getSlug()
            ]);
        }
        return $this->render('blog/new_post.html.twig',[
            'form' => $form->createView()
        ]);
    }

    #[Route('/blog/{page}', name: 'blog', requirements: ['page' => '\d+'])]
    public function blogPage(ManagerRegistry $doctrine, int $page = 1): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAllByDate($page);
        $recents = $repository->findRecents();

        return $this->render('blog/blog.html.twig', [
            'posts' => $posts,
            'recents' => $recents
        ]);
    }


    #[Route('/thankyou',name:'thankyou')]
    public function thanks(): Response
    {
        return $this->render('thankyou.html.twig');
    }
}
