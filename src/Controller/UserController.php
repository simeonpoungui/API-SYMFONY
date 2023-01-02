<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/inscription", name="app_inscription")
     */
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {

        $data = json_decode($request->getContent());
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([

            "nom" =>$data->nom,
            "email" =>$data->email

        ]);

        if ($user == null){
            $user = new User();
            $user->setNom($data->nom);
            $user->setPrenom($data->prenom);
            $user->setDateDeNaissance($data->date_de_naissance);
            $user->setEmail($data->email);
            $user->setBrochure('');
            $user->setPassword($data->password);
            $entityManager->persist($user);
            $entityManager->flush();

        }else{
            return $this->json([
                'code' => 'erreur',
                'message' => 'cet utilisateur existe deja'
            ]);
        }

        return $this->json([
            'code' => 'succes',
            'message' => 'insertion effectuée'
        ]);

    }

    /**
     * @Route("/update", name="app_update")
     */

    public function mise_a_jour(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent());
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find(

            $data->id
        );

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id ' . $data->id
            );
        }
        $user->setNom($data->nom);
        $user->setPrenom($data->prenom);
        $user->setDateDeNaissance($data->date_de_naissance);
        $user->setEmail($data->email);
        $user->setPassword($data->password);
        $entityManager->flush();

        return $this->json([
            'code' => 'succes',
            'message' => 'Mise a jour effectuée'
        ]);

    }

    /**
     * @Route("/delete", name="app_delete")
     */

    public function suppression(EntityManagerInterface $entityManager, Request $request): Response
    {

        $data = json_decode($request->getContent());
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find(
            $data->id
        );

        if (!$user) {
            throw $this->createNotFoundException(
                'No membre found for id ' . $data->id
            );
        }
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json([
            'code' => 'succes',
            'message' => 'suppression effectuée'
        ]);


    }


    /**
     * @Route("/connexion", name="app_connexion")
     */

    public function connexion(EntityManagerInterface $entityManager, Request $request): Response
    {
        $data = json_decode($request->getContent());
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(
            [
                'email'=>$data->email,
                'password'=>$data->password
            ]
        );
        if ($user == null) {

            dd('email ou mot de passe incorect');

        }
        return $this->json([
            'code' => 'succes',
            'message' => 'connexion etablie',
            'user'=>$user
        ]);

    }

    /**
     * @Route("/recuperation", name="app_recuperation")
     */

    public function recuperation(EntityManagerInterface $entityManager, Request $request): Response
    {

        $data = json_decode($request->getContent());
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findAll(
        );
        if (!$user) {
            throw $this->createNotFoundException(
                'No membre found for id ' . $data->id
            );
        }
        $entityManager->flush();
        return $this->json([
            'code' => 'succes',
            'message' => 'liste',
            "user"=>$user
        ]);

    }



    /**
     * @Route("/upload", name="app_upload", methods="POST")
     */

    public function upload_image(EntityManagerInterface $entityManager, Request $request): Response
    {

        //tous les elements qui transitent sur symfonny vient sur request ou data
        $brochureFile = $request->files->get('image');
        $id = (int) $request->request->get('id');
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user){
            return $this->json([
                'code' => 'erreur',
                'message' => 'le user existe pas'
            ]);
        }
        if ($brochureFile) {
            //recuperer le nom original de l'image pathinfo=les informations d'un lien et on redefinit le nom
        $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $originalFilename;
        //deviner l'extension du fichier
        $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
        try {
            //recuperer l'imae et preciser le dossier de stockage
            $brochureFile->move(
                $this->getParameter('brochures_directory'),
                $newFilename
            );
        } catch (FileException $e) {
        }
        $user->setBrochure(
            //recuperer le chemin de l'image
            new File($this->getParameter('brochures_directory').'/'.$newFilename)
        );
    }
        $entityManager->flush();
        return $this->json([
            'code' => 'succes',
            'message' => 'upload effectuée'
        ]);
    }
}
