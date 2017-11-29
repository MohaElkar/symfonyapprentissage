<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Form\AdvertType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AdvertController extends Controller
{
    /**
     * @Route("/{page}", name="oc_platform_index", requirements={"page"="\d+"}, defaults={"page"="1"})
     */
    public function indexAction($page)
    {
        if ($page < 1) {
            throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
        }

        $nbPerPage = $this->getParameter("nbPerPage");

        // Pour récupérer la liste de toutes les annonces : on utilise findAll()
        $listAdverts = $this->getDoctrine()
            ->getManager()
            ->getRepository('OCPlatformBundle:Advert')
            ->getAdverts($page, $nbPerPage)
        ;

        $nbPages = ceil(count($listAdverts) /$nbPerPage);

        if($page > $nbPages){
            throw $this->createNotFoundException("La page ".$page." n'existe pas");
        }

        // L'appel de la vue ne change pas
        return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
            'listAdverts'   => $listAdverts,
            'nbPages'       => $nbPages,
            'page'          => $page
        ));
    }

    /**
     * @Route("/test/", name="oc_platform_test")
     */
    public function testAction(){
        //$entityManager = $this->getDoctrine()->getRepository("OCPlatformBundle:Advert");
        $entityManager = $this->getDoctrine()->getManager();

        //$data = $entityManager->myFindBy(0);
        //$data = $entityManager->findAuthorAndDate("Mohamed", "2017");
        //$data = $entityManager->getAdvertWithCategories(array('Développeur', 'Intégrateur'));
        //$data = $entityManager->getApplicationsWithAdvert(2);

        $advert = new Advert();
        $advert->setAuthor("Moh");
        $advert->setContent("eee");
        $advert->setTitle("Titre de l'annonce");

        $entityManager->persist($advert);
        $entityManager->flush();

        //dump($data);

        return new Response("".$advert->getSlug());
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

        $advert = new Advert();

        $form = $this->createForm(AdvertType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $advert->getImage()->upload();

            $entityManager->persist($advert);
            $entityManager->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            // Puis on redirige vers la page de visualisation de cettte annonce
            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        // Si on n'est pas en POST, alors on affiche le formulaire
        return $this->render('OCPlatformBundle:Advert:add.html.twig',
            array(
                "advert" => $advert,
                "form"  => $form->createView()
            )
        );
    }


    /**
     * @Route("/edit/{id}", name="oc_platform_edit", requirements={"id" = "\d+"})
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Recupération de l'annonce "$id"
        $advert = $em->getRepository("OCPlatformBundle:Advert")->find($id);

        if($advert == null){
            throw new NotFoundHttpException("L'annonce ". $id ." n'existe pas" );
        }

        // Formulaire ici
        $form = $this->createForm(AdvertEditType::class, $advert);

        if ($request->isMethod('POST')) {
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');
            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
            'advert' => $advert,
            'form' => $form
        ));
    }


    /**
     * @Route("/delete/{id}", name="oc_platform_delete", requirements={"id" = "\d+"})
     */
    public function deleteAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $advert = $entityManager->getRepository("OCPlatformBundle:Advert")->find($id);

        if($advert == null){
            throw new NotFoundHttpException("L'annonce ". $id ." n'existe pas");
        }

        foreach ($advert->getCategories() as $cat){
            // On vide les catégories de l'annonce
            $advert->removeCategory($cat);
        }

        $entityManager->flush();

        return $this->render('OCPlatformBundle:Advert:delete.html.twig');
    }


    public function menuAction($limit)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $listAdverts = $entityManager->getRepository("OCPlatformBundle:Advert")->findBy(
            array(),
            array("date" => "desc"),
            $limit,
            0
        );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
            'listAdverts' => $listAdverts
        ));
    }
}