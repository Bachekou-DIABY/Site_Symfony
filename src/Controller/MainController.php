<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use APP\Repository\OfferRepository;
use App\Entity\Offer;
use App\Entity\User;
use App\Form\CreateOfferFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{


    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }
    /**
     * @Route("/", name="homepage")
     */
    public function index(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
      $repository = $em->getRepository(Offer::class);

      $offers = $repository->findBy([],['id'=>'DESC']);
      if (!$offers) {
        return $this->render('main/index.html.twig', [
          'offers' => $offers,
          'controller_name' => 'MainController',

        ]);
      }
      // set an array of custom parameters


      $pagination = $paginator->paginate(
        $offers, // Requête contenant les données à paginer (ici nos articles)
        $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
        10 // Nombre de résultats par page
      );
      $pagination->setCustomParameters([
        'align' => 'center', # center|right (for template: twitter_bootstrap_v4_pagination and foundation_v6_pagination)
        'size' => 'small', # small|large (for template: twitter_bootstrap_v4_pagination)
        'style' => 'bottom',
      ]);
      return $this->render('main/index.html.twig', [
        'offers' =>$offers,
        'pagination' => $pagination,
        'controller_name' => 'MainController',
      ]);

    }

    /**
     * @Route("/manage_offer", name="manage_offer")
     */
    public function manage_offer(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
      $user=$this->getUser();
      $repository = $this->getDoctrine()->getRepository(Offer::class);
      $offers = $repository->findBy(['user' => $user->getId()],['id'=>'DESC']);
      return $this->render('main/manage_offer.html.twig', [
          'offers' => $offers,
          'controller_name' => 'MainController',
      ]);
    }

      /**
       * @Route("/delete_offer/{id}", name="delete_offer")
       */
      public function delete(Offer $offer, EntityManagerInterface $entityManager): Response
      {
          $entityManager->remove($offer);
          $entityManager->flush();
  
          return new RedirectResponse($this->urlGenerator->generate('delete_success'));
        }

      /**
       * @Route("/manage_offer_show/{id}", name="manage_offer_show")
       */
      public function manageOfferShow($id): Response
      {
        $repository = $this->getDoctrine()->getRepository(Offer::class);
        $offer = $repository->findBy(['id' => $id])[0];
          return $this->render('main/manage_offer_show.html.twig', [
              'offer' => $offer,
              'controller_name' => 'MainController',
          ]);
      }

    /**
     * @Route("/create_offer", name="create_offer")
     */
    public function add(Request $request, ): Response
    {

        $Offer = new Offer();
        $form = $this->createForm(CreateOfferFormType::class, $Offer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $Offer->setUser($this->getUSer());
            $Offer->setDate(new \DateTime());
            if (null !== $Offer->getImage()) {
                $file = $form->get('Image')->getData();
                $fileName = uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'), // Le dossier dans le quel le fichier va etre charger
                      $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $Offer->setImage($fileName);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($Offer);
            $entityManager->flush();
            return new RedirectResponse($this->urlGenerator->generate('creation_success'));
        }

        return $this->render('main/create_offer.html.twig', [
            'controller_name' => 'MainController',
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/creation_success", name="creation_success")
     */
    public function CreationSuccess(): Response
    {
        return $this->render('main/creation_success.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

        /**
     * @Route("/delete_success", name="delete_success")
     */
    public function DeleteSuccess(): Response
    {
        return $this->render('main/delete_success.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    /**
     * @Route("/offers/{id}", name="offer")
     */
    public function ShowOffer($id): Response
    {
      $repository = $this->getDoctrine()->getRepository(Offer::class);
      $offer = $repository->findBy(['id' => $id])[0];
        return $this->render('main/show.html.twig', [
            'offer' => $offer,
            'controller_name' => 'MainController',
        ]);
    }
    
    /**
     * @Route("/search", name="search")
     */
    public function Search(): Response
    {
      $keyword=$_GET['q'];
      $repository = $this->getDoctrine()->getRepository(Offer::class);
      $offers = $repository->findByKeyword($keyword);
        return $this->render('main/search.html.twig', [
            'offers' => $offers,
            'controller_name' => 'MainController',
        ]);

      }
}
