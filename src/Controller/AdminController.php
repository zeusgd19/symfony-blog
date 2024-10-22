<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Image;
use App\Form\CategoryFormType;
use App\Form\ImageFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminController extends AbstractController
{
    #[Route('/admin/images', name: 'app_images')]
    public function images(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $repository = $doctrine->getRepository(Image::class);
        $images = $repository->findAll();
        $image = new Image();
        $form = $this->createForm(ImageFormType::class, $image);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                // Move the file to the directory where images are stored
                try {

                    $file->move(
                        $this->getParameter('images_directory'), $newFilename
                    );
                    $filesystem = new Filesystem();
                    $filesystem->copy(
                        $this->getParameter('images_directory') . '/'. $newFilename,
                        $this->getParameter('portfolio_directory') . '/'.  $newFilename, true);

                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'file$filename' property to store the PDF file name
                // instead of its contents
                $image->setFile($newFilename);
            }
            $image = $form->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($image);
            $entityManager->flush();
        }
        return $this->render('admin/images.html.twig', array(
            'form' => $form->createView(),
            'images' => $images
        ));
    }


    #[Route('/admin/categories', name: 'app_categories')]
    public function categories(ManagerRegistry $doctrine, Request $request): Response
    {
        $repository = $doctrine->getRepository(Category::class);

        $categories = $repository->findAll();
        $category = new Category();
        $formulario = $this->createForm(CategoryFormType::class, $category);
        $formulario->handleRequest($request);

        if($formulario->isSubmitted() && $formulario->isValid()){
            $category = $formulario->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($category);
            $entityManager->flush();
        }
        return $this->render('admin/categories.html.twig', [
            'form' => $formulario->createView(),
            'categories' => $categories
        ]);
    }

}
