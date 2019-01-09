<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Users;
use App\Form\Admin\UsersType;
use App\Repository\Admin\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/users")
 */
class UsersController extends AbstractController
{
    /**
     * @Route("/", name="admin_users_index", methods="GET")
     */
    public function index(UsersRepository $usersRepository): Response
    {
        return $this->render('admin/users/index.html.twig', ['users' => $usersRepository->findAll()]);
    }

    /**
     * @Route("/new", name="admin_users_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $this->addFlash('success','Üye Ekleme ');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_users_show", methods="GET")
     */
    public function show(Users $user): Response
    {
        return $this->render('admin/users/show.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/{id}/edit", name="admin_users_edit", methods="GET|POST")
     */
    public function edit(Request $request, Users $user): Response
    {
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success','Üye Güncelleme');
            return $this->redirectToRoute('admin_users_index', ['id' => $user->getId()]);
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_users_delete", methods="DELETE")
     */
    public function delete(Request $request, Users $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            $this->addFlash('warning','Üye Silindi!');
        }

        return $this->redirectToRoute('admin_users_index');
    }
}
