<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\AdvertSkill;
use OC\PlatformBundle\Entity\Application;
use OC\PlatformBundle\Entity\Image;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AdvertController extends Controller
{
    public function indexAction($page)
    {
        // On ne sait pas combien de pages il y a
        // Mais on sait qu'une page doit être supérieure ou égale à 1
        if ($page < 1) {
            // On déclenche une exception NotFoundHttpException, cela va afficher
            // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
            throw new NotFoundHttpException('Page "' . $page . '" inexistante.');
        }

        // Ici, on récupérera la liste des annonces, puis on la passera au template
        // Notre liste d'annonce en dur
        $listAdverts = array(
            array(
                'title' => 'Recherche développpeur Symfony',
                'id' => 1,
                'author' => 'Alexandre',
                'content' => 'Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…',
                'date' => new \Datetime()),
            array(
                'title' => 'Mission de webmaster',
                'id' => 2,
                'author' => 'Hugo',
                'content' => 'Nous recherchons un webmaster capable de maintenir notre site internet. Blabla…',
                'date' => new \Datetime()),
            array(
                'title' => 'Offre de stage webdesigner',
                'id' => 3,
                'author' => 'Mathieu',
                'content' => 'Nous proposons un poste pour webdesigner. Blabla…',
                'date' => new \Datetime())
        );
dump($listAdverts);
        // Mais pour l'instant, on ne fait qu'appeler le template
        return $this->render('OCPlatformBundle:Advert:index.html.twig', array(

            'listAdverts' => $listAdverts

        ));
    }

    /**
     * @Route("/test/", name="oc_platform_test")
     */
    public function testAction(){
        $entityManager = $this->getDoctrine()->getRepository("OCPlatformBundle:Advert");

        $data = $entityManager->myFindBy(0);
        $data = $entityManager->findAuthorAndDate("Mohamed", "2017");

        dump($data);

        return new Response("Hello");
    }


    /**
     * @Route("/advert/{id}", name="oc_platform_view", requirements={"id" = "\d+"})
     */
    public function viewAction($id)
    {
        $entityManager  = $this->getDoctrine()->getManager();
        $advert         = $entityManager->getRepository("OCPlatformBundle:Advert")->find($id);

        // On vérifie que advert existe
        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On recupere les application de l'annonce (advert).
        $repositoryApplication  = $entityManager->getRepository("OCPlatformBundle:Application");
        $listApplications       = $repositoryApplication->findBy(array("advert" => $advert));

        // récupération des advertSkills
        $advertSkills = $entityManager->getRepository("OCPlatformBundle:AdvertSkill")->findBy(array("advert"=>$advert));

        dump($advertSkills);

        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert'            => $advert,
            'listApplications'  => $listApplications,
            'listAdvertSkills'  => $advertSkills
        ));
    }


    /**
     * @Route("/add", name="oc_platform_add")
     */
    public function addAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        // creation de l'entity advert
        $advert = new Advert();
        $advert->setTitle("Recherche dev symfony");
        $advert->setAuthor("Mohamed");
        $advert->setContent("Bonjour, je recherche un dev symfony rapidement");

        // Ajout de l'image
        $image = new Image();
        $image->setUrl("http://sdz-upload.s3.amazonaws.com/prod/upload/job-de-reve.jpg");
        $image->setAlt("Job de reve");

        $advert->setImage($image);

        // Toutes les compétences possibles
        $listSkills = $entityManager->getRepository("OCPlatformBundle:Skill")->findAll();

        // Pour chaque compétence
        foreach ($listSkills as $skill){
            $advertSkill = new AdvertSkill();
            $advertSkill->setAdvert($advert);
            $advertSkill->setSkill($skill);
            $advertSkill->setLevel("Expert");

            $entityManager->persist($advertSkill);
        }

        // Application
        $application = new Application();
        $application->setAuthor("Boss");
        $application->setContent("Le text du boss");
        $application->setAdvert($advert);

        // Persist advert
        $entityManager->persist($advert);

        // On persiste l'application
        $entityManager->persist($application);

        // enregistre les donnés dans la db
        $entityManager->flush();

        // Si la requête est en POST, c'est que le visiteur a soumis le formulaire
        if ($request->isMethod('POST')) {
            // Ici, on s'occupera de la création et de la gestion du formulaire

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            // Puis on redirige vers la page de visualisation de cettte annonce
            return $this->redirectToRoute('oc_platform_view', array('id' => 5));
        }

        // Si on n'est pas en POST, alors on affiche le formulaire
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array("advert" => $advert));
    }


    /**
     * @Route("/edit/{id}", name="oc_platform_edit", requirements={"id" = "\d+"})
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Recupération de l'annonce "$id"
        $advert = $em->getRepository("OCPlatformBundle:Advert")->find($id);

        $listCategories = $em->getRepository("OCPlatformBundle:Category")->findAll();

        foreach ($listCategories as $category){
            $advert->addCategory($category);
        }

        $em->flush();


        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
            'advert' => $advert
        ));
    }


    /**
     * @Route("/delete/{id}", name="oc_platform_delete", requirements={"id" = "\d+"})
     */
    public function deleteAction($id)
    {
        // Ici, on récupérera l'annonce correspondant à $id

        // Ici, on gérera la suppression de l'annonce en question

        return $this->render('OCPlatformBundle:Advert:delete.html.twig');
    }


    public function menuAction($limit)
    {
        // On fixe en dur une liste ici, bien entendu par la suite
        // on la récupérera depuis la BDD !
        $listAdverts = array(
            array('id' => 2, 'title' => 'Recherche développeur Symfony'),
            array('id' => 5, 'title' => 'Mission de webmaster'),
            array('id' => 9, 'title' => 'Offre de stage webdesigner')
        );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
            // Tout l'intérêt est ici : le contrôleur passe
            // les variables nécessaires au template !
            'listAdverts' => $listAdverts
        ));
    }
}