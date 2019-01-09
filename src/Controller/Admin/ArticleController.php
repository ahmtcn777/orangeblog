<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Article;
use App\Form\Admin\ArticleType;
use App\Repository\Admin\ArticleRepository;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", name="admin_article_index", methods="GET")
     */
    public function index(ArticleRepository $articleRepository,CategoryRepository $categoryRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $sql='SELECT * FROM article ORDER BY status ASC ';
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $article=$statement->fetchAll();
        return $this->render('admin/article/index.html.twig', ['articles' => $article]);
    }

    /**
     * @Route("/new", name="admin_article_new", methods="GET|POST")
     */
    public function new(Request $request,CategoryRepository $categoryRepository): Response
    {
        $catlist=$categoryRepository->findAll();
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();
            $this->addFlash('success','Yazı Ekleme');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/new.html.twig', [
            'article' => $article,
            'catlist' => $catlist,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_article_show", methods="GET")
     */
    public function show(Article $article): Response
    {
        return $this->render('admin/article/show.html.twig', ['article' => $article]);
    }

    /**
     * @Route("/edit/{id}", name="admin_article_edit", methods="GET|POST")
     */
    public function edit(Request $request,$id,ArticleRepository $articleRepository , Article $article,CategoryRepository $categoryRepository): Response
    {

        $catlist=$categoryRepository->findAll();
        $catname=$categoryRepository->findBy(
        ['id' => $article->getCategoryid()]
        );
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success','Kayıt Güncelleme');
            return $this->redirectToRoute('admin_article_edit', ['id' => $article->getId()]);
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'catlist' => $catlist,
            'catname' => $catname,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/iedit", name="admin_article_iedit", methods="GET|POST")
     */
    public function iedit(Request $request,$id, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        $user=$this->getUser();
        $userid=$user->getid();
        $articleUserid=$article->getUserid();
        if ($userid == $articleUserid) {
            if ($form->isSubmitted()) {

                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('admin_article_edit', ['id' => $article->getId()]);
            }
        }
        return $this->render('admin/article/image_edit.html.twig', [
            'article' => $article,
            'id' => $id,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/iupdate", name="admin_article_iupdate", methods="POST")
     */
    public function iupdate(Request $request,$id, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        //dump($request);
        //die();
        $file=$request->files->get('imagename');
        $fileName=$this->generateUniqueFileName().'.'.$file->guessExtension();
        // Move the file to the directory where brochures are stored
        try {
            $file->move(
                $this->getParameter('images_directory'),
                $fileName
            );
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
        $article->setImage($fileName);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('admin_article_iedit', ['id' => $article->getId()]);
    }


    /**
     * @Route("/{id}", name="admin_article_delete", methods="DELETE")
     */
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($article);
            $em->flush();
            $this->addFlash('warning','Yazı Silindi!');
        }

        return $this->redirectToRoute('admin_article_index');
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    /**
     * @Route("/onayla/{id}", name="admin_article_accept", methods="GET|POST")
     */
    public function onayla(Request $request,$id,ArticleRepository $articleRepository , Article $article,CategoryRepository $categoryRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $sql='SELECT * FROM article ORDER BY status ASC ';
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $article=$statement->fetchAll();
        $articles=$articleRepository->findBy([
            'id' => $id
        ]);
        $articles[0]->setStatus("True");
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success','Kayıt Güncelleme');
        return $this->redirectToRoute('admin_article_index');

    }
}
