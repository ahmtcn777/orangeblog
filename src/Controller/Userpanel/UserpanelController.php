<?php

namespace App\Controller\Userpanel;

use App\Entity\Admin\Article;
use App\Entity\Admin\Image;
use App\Entity\User;
use App\Form\Admin\ArticleType;
use App\Form\UserType;
use App\Form\Admin\ImageType;
use App\Repository\Admin\ArticleRepository;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\SettingRepository;
use App\Repository\Admin\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/userpanel")
 */
class UserpanelController extends AbstractController
{
    /**
     * @Route("/", name="userpanel")
     */
    public function index(SettingRepository $settingRepository)
    {
        $data=$settingRepository->findAll();
        return $this->render('userpanel/show.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @Route("/useredit", name="userpanel_edit")
     */
    public function edit(SettingRepository $settingRepository,Request $request,UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $usersession=$this->getUser();
        $user=$this->getDoctrine()->getRepository(User::class)->find($usersession->getid());
        $userpassword=$user->getpassword();
        if ($request->isMethod('POST')){
            if($request->request->get("password") != $userpassword){
                $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get("password")));
            }
            $user->setName($request->request->get("name"));
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success','Ayarlarınız kaydedildi.');
            return $this->redirectToRoute('userpanel');
        }
        $data=$settingRepository->findAll();
        return $this->render('userpanel/duzenle.html.twig', [
            'data' => $data,
            'user' => $user,
        ]);
    }


    /**
     * @Route("/addblog", name="add_blog")
     */
    public function addBlog(SettingRepository $settingRepository,Request $request,CategoryRepository $categoryRepository,ArticleRepository $articleRepository): Response
    {
        $usersession=$this->getUser();
        $userid=$usersession->getid();
        $catlist=$categoryRepository->findAll();
        $article=new Article();
        if($request->isMethod('POST')){
            $article->setTitle($request->request->get("title"));
            $article->setDescription($request->request->get("decription"));
            $article->setKeywords($request->request->get("keywords"));
            $article->setCategoryid($request->request->get("categoryid"));
            $article->setDetail($request->request->get("detail"));
            $article->setStatus("False");
            $article->setUserid($userid);
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();
            //$this->getDoctrine()->getManager()->flush();
            $this->addFlash('success','Blog Onay bekliyor. Lütfen resim yükleyiniz!');
            return $this->redirectToRoute('bloglarim');
        }
        $data=$settingRepository->findAll();
        return $this->render('userpanel/addblog.html.twig', [
            'data' => $data,
            'catlist' => $catlist,
            'article' => $article,
        ]);
    }



    /**
     * @Route("/bloglarim", name="bloglarim")
     */
    public function bloglarim(SettingRepository $settingRepository,ArticleRepository $articleRepository,CategoryRepository $categoryRepository)
    {
        $usersession=$this->getUser();
        $userid=$usersession->getid();
        $data=$settingRepository->findAll();
        $catlist=$categoryRepository->findAll();
        $articles=$articleRepository->findBy(
            ['userid' => $userid]
        );

        return $this->render('userpanel/bloglarim.html.twig', [
            'data' => $data,
            'articles' => $articles,
            'catlist' => $catlist,
        ]);
    }

    /**
     * @Route("/{aid}/galleryadd", name="blog_gallery_add", methods="GET|POST")
     */
    public function galleryadd(Request $request,$aid,ImageRepository $imageRepository): Response
    {
        $imagelist=$imageRepository->findBy(
            ['articleid' => $aid]
        );
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($request->files->get('imagename')) {
            //if ($form->isSubmitted()) {
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
            $image->setImage($fileName);
            $image->setArticleid($aid);
            $em = $this->getDoctrine()->getManager();
            $em->persist($image);
            $em->flush();

            return $this->redirectToRoute('blog_gallery_add',array('aid' => $aid));
        }

        return $this->render('userpanel/galleryadd.html.twig', [
            'image' => $image,
            'imagelist' => $imagelist,
            'aid' => $aid,
            'form' => $form->createView(),
        ]);
    }



    /**
     * @Route("/blogiedit/{id}", name="blog_iedit", methods="GET|POST")
     */
    public function blogiedit(Request $request,$id, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        $user=$this->getUser();
        $userid=$user->getid();
        $articleUserid=$article->getUserid();
        if ($userid == $articleUserid){
            if ($form->isSubmitted() ) {

                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('blog_iedit', ['id' => $article->getId()]);
            }
        }


        return $this->render('userpanel/image_edit.html.twig', [
            'article' => $article,
            'id' => $id,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("blogiupdate/{id}/", name="blog_iupdate", methods="POST")
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
        return $this->redirectToRoute('blog_iedit', ['id' => $article->getId()]);
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
}
