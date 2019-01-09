<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\User;
use App\Form\Admin\MessagesType;
use App\Form\UserType;
use App\Repository\Admin\ArticleRepository;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\SettingRepository;
use App\Repository\Admin\UsersRepository;
use App\Repository\Admin\ImageRepository;
use App\Repository\UserRepository;
use function Sodium\crypto_sign_detached;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class HomeController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository,UsersRepository $usersRepository,CategoryRepository $categoryRepository,ArticleRepository $articleRepository)
    {
        $users=$usersRepository->findAll();
        $post=$articleRepository->findAll();
        $data=$settingRepository->findAll();
        $cats=$this->categoryTree();
        $cats[0]='<ul id="menu-v">';

        //Get data for slider
        $em = $this->getDoctrine()->getManager();
        $sql='SELECT * FROM article WHERE status="True" ORDER BY ID DESC LIMIT 3';
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $sliders=$statement->fetchAll();
        //dump($sliders);
        //die();

        //Get data for indexpost
        $em = $this->getDoctrine()->getManager();
        $sql='SELECT * FROM article WHERE status="True" ORDER BY created_at DESC LIMIT 5';
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $index_posts=$statement->fetchAll();

        if ($data[0]->getStatus() == 'Aktif'){
            return $this->render('home/index.html.twig', [
                'data' => $data,
                'cats' => $cats,
                'post' => $post,
                'users' => $users,
                'sliders' => $sliders,
                'posts' => $index_posts,
            ]);
        }
        else{
            return $this->render('home/bakim.html.twig', [
                'controller_name' => 'HomeController',
            ]);
        }
    }
    /**
     * @Route("/hakkimizda", name="hakkimizda")
     */
    public function hakkimizda(SettingRepository $settingRepository,CategoryRepository $categoryRepository)
    {
        $data=$settingRepository->findAll();
        $cats[0]='<ul id="menu-v">';
        return $this->render('home/hakkimizda.html.twig', [
            'data' => $data,
        ]);
    }
    /**
     * @Route("/iletisim", name="iletisim", methods="GET|POST")
     */
    public function iletisim(SettingRepository $settingRepository,CategoryRepository $categoryRepository,Request $request)
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);
        $submittedToken=$request->request->get('token');
        if ($form->isSubmitted() ) {
            if ($this->isCsrfTokenValid('form-messages',$submittedToken)) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($message);
                $em->flush();
                $this->addFlash('success', 'Mesaj Gönderme');
                return $this->redirectToRoute('iletisim');
            }
        }

        $data=$settingRepository->findAll();
        return $this->render('home/iletisim.html.twig', [
            'data' => $data,
            'message' => $message,
        ]);
    }

    public function categoryTree($parent=0,$user_tree_array=''){
        if(!is_array($user_tree_array)){
            $user_tree_array=array();
        }
        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM category WHERE status='True' AND parentid=".$parent;
        $statement=$em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid',$parent);
        $statement->execute();
        $result=$statement->fetchAll();
//dump($result);
//die();
        if(count($result) > 0){
            $user_tree_array[]="<ul>";
            foreach ($result as $row){
                //print_r($row);
                //die();
                $user_tree_array[]="<li><a href='/category/".$row['id']."'>".$row['title']."</a>";
                $user_tree_array=$this->categoryTree($row['id'],$user_tree_array);
            }
            $user_tree_array[]="</li></ul>";
        }
        return $user_tree_array;
    }

    /**
     * @Route("/category/{catid}", name="category_article", methods="GET")
     */
    public function CategoryArticle($catid,CategoryRepository $categoryRepository,SettingRepository $settingRepository,UsersRepository $usersRepository)
    {

        $data=$settingRepository->findAll();
        $users=$usersRepository->findAll();
        $cats=$this->categoryTree();
        $cats[0]='<ul id="menu-v">';
        $catdata=$categoryRepository->findBy(
            ['id' => $catid]
        );
        $catlist=$categoryRepository->findBy(
            ['parentid' => $catid]
        );
        $em = $this->getDoctrine()->getManager();
        $sql='SELECT * FROM article WHERE status="True" AND categoryid = :catid';
        $statement=$em->getConnection()->prepare($sql);
        $statement->bindValue('catid',$catid);
        $statement->execute();
        $articles=$statement->fetchAll();
        if($catdata[0]->getParentid() != 0){
            return $this->render('home/articles.html.twig', [
                'data' => $data,
                'cats' => $cats,
                'catdata' => $catdata,
                'articles' => $articles,
                'users' => $users,
            ]);
        }
        else{
            return $this->render('home/kategoriler.html.twig', [
                'data' => $data,
                'cats' => $cats,
                'catdata' => $catdata,
                'articles' => $articles,
                'users' => $users,
                'catlist' => $catlist,
            ]);
        }

    }

    /**
     * @Route("/article/{id}", name="article_detail", methods="GET")
     */
    public function ArticleDetail($id,ImageRepository $imageRepository,ArticleRepository $articleRepository, SettingRepository $settingRepository,UsersRepository $usersRepository)
    {

        $data=$settingRepository->findAll();
        $users=$usersRepository->findAll();
        $article=$articleRepository->findBy([
            'id' => $id
            ]);
        $gallery=$imageRepository->findBy([
           'articleid'=> $id
        ]);

        $cats=$this->categoryTree();
        $cats[0]='<ul id="menu-v">';
        //dump($article);
        //die();
        return $this->render('home/article.html.twig', [
            'data' => $data,
            'article' => $article,
            'gallery' => $gallery,
            'users' => $users,
            'cats' => $cats,
        ]);
    }


    /**
     * @Route("/kayitol", name="kayitol", methods="GET|POST")
     */
    public function kayitol(Request $request,SettingRepository $settingRepository,UserPasswordEncoderInterface $passwordEncoder,UserRepository $userRepository)
    {
        $data=$settingRepository->findAll();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        $submittedToken=$request->request->get('_csrf_token');

        if ($this->isCsrfTokenValid('kayitol-form',$submittedToken)) {
            if ($form->isSubmitted() ) {
                $emaildata=$userRepository->findBy(
                    ['email' => $user->getEmail()]
                );
                //dump($emaildata);
                //die();
                if($emaildata == null){
                    $user->setRoles('ROLE_USER');
                    $password = $passwordEncoder->encodePassword($user, $user->getPassword());
                    $user->setPassword($password);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success','Üye kaydınız tamamlanmıştır. Login olabilirsiniz.');
                    return $this->redirectToRoute('app_login');
                }
                else{
                    $this->addFlash('error','Mail adresi zaten kayıtlı! '.$user->getEmail());

                }
            }
        }
        return $this->render('home/kayitol.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'data' => $data,
        ]);

    }

}
